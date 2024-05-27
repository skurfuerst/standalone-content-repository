<?php

use App\NodeTreePrinter;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

require __DIR__ . '/vendor/autoload.php';

$contentRepository = App\Common::getContentRepository();

$userWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName(
    WorkspaceName::fromString('user-sebastian')
);

$contentRepository->handle(
    SetNodeProperties::create(
        contentStreamId: $userWorkspace->currentContentStreamId,
        nodeAggregateId: NodeAggregateId::fromString('d022c323-75e5-4628-9526-d6f085b7b662'),
        originDimensionSpacePoint: OriginDimensionSpacePoint::fromArray([]),
        propertyValues: PropertyValuesToWrite::fromArray([
            'title' => 'IPC 2024',
            'modificationDate' => new \DateTimeImmutable(),
        ])
    )
)->block();

$contentRepository->handle(
    RemoveNodeAggregate::create(
        contentStreamId: $userWorkspace->currentContentStreamId,
        nodeAggregateId: NodeAggregateId::fromString('1330cb65-5b57-4a10-9c53-1408b381eea7'),
        coveredDimensionSpacePoint: DimensionSpacePoint::fromArray([]),
        nodeVariantSelectionStrategy: NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS
    )
)->block();

echo sprintf("🟢 Updated content in user workspace\n");

NodeTreePrinter::print($contentRepository);
