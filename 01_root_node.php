<?php

use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

require __DIR__ . '/vendor/autoload.php';

$contentRepository = App\Common::getContentRepository();

$rootNodeTypeName = NodeTypeName::fromString('MyProject:Root');

$contentRepository->handle(CreateRootWorkspace::create(
    WorkspaceName::forLive(),
    ContentStreamId::fromString('live-cs-id')
));

echo "🟢 finished setting up live workspace / content stream\n";

$contentRepository->handle(CreateRootNodeAggregateWithNode::create(
    WorkspaceName::forLive(),
    NodeAggregateId::fromString('norman-nodely-van-rootford'),
    $rootNodeTypeName
));

echo "🟢 finished setting up root node\n";

\App\NodeTreePrinter::print($contentRepository);
