<?php

namespace App;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjectionFactory;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ConfigurationBasedContentDimensionSource;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Factory\AuthProviderFactoryInterface;
use Neos\ContentRepository\Core\Factory\CommandHooksFactory;
use Neos\ContentRepository\Core\Factory\ContentRepositoryFactory;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositorySubscriberFactories;
use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\ContentRepository\Core\Feature\Security\StaticAuthProvider;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ArrayNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\CollectionTypeDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ScalarNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\UriNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectArrayDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectBoolDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectFloatDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectIntDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectStringDenormalizer as ValueObjectStringDenormalizerAlias;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionFactoryInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionStoreInterface;
use Neos\ContentRepository\Dbal\SubscriptionStore\DoctrineSubscriptionStore;
use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Neos\EventStore\EventStoreInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * This class is modeled after https://github.com/neos/neos-development-collection/blob/38385ac2d401358c34f5a6c1e2a6638b192fd78a/Neos.ContentRepositoryRegistry/Classes/ContentRepositoryRegistry.php
 *
 * -> but with less configuration and more hard-wiring
 */
final class StandaloneContentRepositoryRegistry
{

    /**
     * Cache to ensure the same CR is returned every time.
     *
     * @var array<string, ContentRepositoryFactory>
     */
    private array $factoryInstances = [];

    /**
     * @param array<mixed> $dimensionConfiguration
     * @param array<mixed> $nodeTypeConfiguration
     */
    public function __construct(
        private readonly Connection $dbalConnection,
        private readonly array $dimensionConfiguration,
        private readonly array $nodeTypeConfiguration,
    ) {
    }

    public function get(ContentRepositoryId $contentRepositoryId): ContentRepository
    {
        return $this->getFactory($contentRepositoryId)->getOrBuild();
    }

    /**
     * @param ContentRepositoryId $contentRepositoryId
     * @param ContentRepositoryServiceFactoryInterface<T> $contentRepositoryServiceFactory
     * @return T
     * @template T of ContentRepositoryServiceInterface
     */
    public function buildService(ContentRepositoryId $contentRepositoryId, ContentRepositoryServiceFactoryInterface $contentRepositoryServiceFactory): ContentRepositoryServiceInterface
    {
        return $this->getFactory($contentRepositoryId)->buildService($contentRepositoryServiceFactory);
    }

    private function getFactory(ContentRepositoryId $contentRepositoryId): ContentRepositoryFactory
    {
        // This cache is CRUCIAL, because it ensures that the same CR always deals with the same objects internally, even if multiple services
        // are called on the same CR.
        if (!array_key_exists($contentRepositoryId->value, $this->factoryInstances)) {
            $this->factoryInstances[$contentRepositoryId->value] = $this->buildFactory($contentRepositoryId);
        }
        return $this->factoryInstances[$contentRepositoryId->value];
    }

    private function buildFactory(ContentRepositoryId $contentRepositoryId): ContentRepositoryFactory
    {
        $clock = $this->buildClock();
        return new ContentRepositoryFactory(
            contentRepositoryId: $contentRepositoryId,
            eventStore: $this->buildEventStore($contentRepositoryId, $clock),
            nodeTypeManager: $this->buildNodeTypeManager(),
            contentDimensionSource: $this->buildContentDimensionSource(),
            propertySerializer: $this->buildPropertySerializer(),
            authProviderFactory: $this->buildAuthProviderFactory(),
            clock: $clock,
            subscriptionStore: $this->buildSubscriptionStore($contentRepositoryId, $clock),
            contentGraphProjectionFactory: $this->buildContentGraphProjectionFactory(),
            contentGraphCatchUpHookFactory: null,
            commandHooksFactory: new CommandHooksFactory(),
            additionalSubscriberFactories: ContentRepositorySubscriberFactories::createEmpty(),
            logger: null
        );
    }

    private function buildEventStore(ContentRepositoryId $contentRepositoryId, ClockInterface $clock): EventStoreInterface
    {
        return new DoctrineEventStore(
            $this->dbalConnection,
            'cr_' . $contentRepositoryId->value . '_events',
            $clock
        );
    }

    private function buildSubscriptionStore(ContentRepositoryId $contentRepositoryId, ClockInterface $clock): SubscriptionStoreInterface
    {
        return new DoctrineSubscriptionStore(sprintf('cr_%s_subscriptions', $contentRepositoryId->value), $this->dbalConnection, $clock);
    }

    private function buildContentGraphProjectionFactory(): ContentGraphProjectionFactoryInterface
    {
        return new DoctrineDbalContentGraphProjectionFactory(
            $this->dbalConnection
        );
    }

    private function buildNodeTypeManager(): NodeTypeManager
    {
        return NodeTypeManager::createFromArrayConfiguration(
            $this->nodeTypeConfiguration
        );
    }

    private function buildContentDimensionSource(): ContentDimensionSourceInterface
    {
        return new ConfigurationBasedContentDimensionSource($this->dimensionConfiguration);
    }

    private function buildPropertySerializer(): Serializer
    {
        $normalizers = [];

        $normalizers[] = new DateTimeNormalizer();
        $normalizers[] = new ScalarNormalizer();
        $normalizers[] = new BackedEnumNormalizer();
        $normalizers[] = new ArrayNormalizer();
        $normalizers[] = new UriNormalizer();
        $normalizers[] = new UriNormalizer();
        $normalizers[] = new ValueObjectArrayDenormalizer();
        $normalizers[] = new ValueObjectBoolDenormalizer();
        $normalizers[] = new ValueObjectFloatDenormalizer();
        $normalizers[] = new ValueObjectIntDenormalizer();
        $normalizers[] = new ValueObjectStringDenormalizerAlias();
        $normalizers[] = new CollectionTypeDenormalizer();

        return new Serializer($normalizers);
    }

    private function buildAuthProviderFactory(): AuthProviderFactoryInterface
    {
        // todo add default implementation like this to the core
        return new class implements AuthProviderFactoryInterface
        {
            public function build(ContentRepositoryId $contentRepositoryId, ContentGraphReadModelInterface $contentGraphReadModel): AuthProviderInterface
            {
                return new StaticAuthProvider(UserId::forSystemUser());
            }
        };
    }

    private function buildClock(): ClockInterface
    {
        return new class implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable();
            }
        };
    }
}
