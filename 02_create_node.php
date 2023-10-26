<?php

use App\NodeTreePrinter;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
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


$contentRepository->handle(
    CreateNodeAggregateWithNode::create(
        contentStreamId: $liveWorkspace->currentContentStreamId,
        nodeAggregateId: NodeAggregateId::create(),
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

NodeTreePrinter::print($contentRepository);
