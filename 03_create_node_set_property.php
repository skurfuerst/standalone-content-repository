<?php

use App\NodeTreePrinter;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

require __DIR__ . '/vendor/autoload.php';

$contentRepository = App\Common::getContentRepository();
$liveWorkspace = $contentRepository
    ->getWorkspaceFinder()
    ->findOneByName(WorkspaceName::fromString('live'));
$rootNodeId = App\Common::getRootNodeId();

$nodeId1 = NodeAggregateId::create();

$contentRepository->handle(
    CreateNodeAggregateWithNode::create(
        contentStreamId: $liveWorkspace->currentContentStreamId,
        nodeAggregateId: $nodeId1,
        nodeTypeName: NodeTypeName::fromString('MyProject:Page'),
        originDimensionSpacePoint: OriginDimensionSpacePoint::fromArray([]),
        parentNodeAggregateId: $rootNodeId,
        initialPropertyValues: PropertyValuesToWrite::fromArray([
            'title' => 'My Title',
//            'description' => 'description'
        ])
    )
)->block();

echo "🟢 Inserted a page\n";


$contentRepository->handle(
    SetNodeProperties::create(
        contentStreamId: $liveWorkspace->currentContentStreamId,
        nodeAggregateId: $nodeId1,
        originDimensionSpacePoint: OriginDimensionSpacePoint::fromArray([]),
        propertyValues: PropertyValuesToWrite::fromArray([
            'creationDate' => new \DateTimeImmutable(),
        ])
    )
)->block();

echo "🟢 Modified properties\n";

NodeTreePrinter::print($contentRepository);
