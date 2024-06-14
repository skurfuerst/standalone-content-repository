<?php

namespace App;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjectionFactory;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ConfigurationBasedContentDimensionSource;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryFactory;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Factory\ProjectionsAndCatchUpHooksFactory;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
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
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ContentStream\ContentStreamProjectionFactory;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceProjectionFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
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

    private DbalClientInterface $dbalClient;

    /**
     * @param array<mixed> $dimensionConfiguration
     * @param array<mixed> $nodeTypeConfiguration
     * @param array<mixed> $additionalProjectionFactories
     */
    public function __construct(
        Connection $dbalConnection,
        private readonly array $dimensionConfiguration,
        private readonly array $nodeTypeConfiguration,
        private readonly array $additionalProjectionFactories,
    ) {

        $this->dbalClient = new class($dbalConnection) implements DbalClientInterface {
            public function __construct(
                private readonly Connection $dbalConnection
            ) {
            }

            public function getConnection(): \Doctrine\DBAL\Connection
            {
                return $this->dbalConnection;
            }
        };

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
            $contentRepositoryId,
            $this->buildEventStore($contentRepositoryId, $clock),
            $this->buildNodeTypeManager(),
            $this->buildContentDimensionSource(),
            $this->buildPropertySerializer(),
            $this->buildProjectionsFactory(),
            $this->buildProjectionCatchUpTrigger($contentRepositoryId),
            $this->buildUserIdProvider(),
            $clock,
        );
    }

    private function buildEventStore(ContentRepositoryId $contentRepositoryId, ClockInterface $clock): EventStoreInterface
    {
        return new DoctrineEventStore(
            $this->dbalClient->getConnection(),
            'cr_' . $contentRepositoryId->value . '_events',
            $clock
        );
    }

    private function buildNodeTypeManager(): NodeTypeManager
    {
        return new NodeTypeManager(
            fn() => $this->nodeTypeConfiguration
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

    private function buildProjectionsFactory(): ProjectionsAndCatchUpHooksFactory
    {
        $projectionsFactory = new ProjectionsAndCatchUpHooksFactory();
        $projectionsFactory->registerFactory(new DoctrineDbalContentGraphProjectionFactory($this->dbalClient), []);
        $projectionsFactory->registerFactory(new WorkspaceProjectionFactory($this->dbalClient), []);
        $projectionsFactory->registerFactory(new ContentStreamProjectionFactory($this->dbalClient), []);

        foreach ($this->additionalProjectionFactories as $projectionFactory) {
            $projectionsFactory->registerFactory($projectionFactory, []);
        }

        return $projectionsFactory;
    }

    private function buildProjectionCatchUpTrigger(ContentRepositoryId $contentRepositoryId): ProjectionCatchUpTriggerInterface
    {
        return new class($this, $contentRepositoryId) implements ProjectionCatchUpTriggerInterface {
            public function __construct(
                private readonly StandaloneContentRepositoryRegistry $registry,
                private readonly ContentRepositoryId $contentRepositoryId,
            ) {
            }

            public function triggerCatchUp(Projections $projections): void
            {
                $contentRepository = $this->registry->get($this->contentRepositoryId);
                foreach ($projections as $projection) {
                    $projectionClassName = get_class($projection);
                    $contentRepository->catchUpProjection($projectionClassName, CatchUpOptions::create());
                }
            }
        };
    }

    private function buildUserIdProvider(): UserIdProviderInterface
    {
        return new class implements UserIdProviderInterface {
            public function getUserId(): UserId
            {
                return UserId::fromString('system');
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
