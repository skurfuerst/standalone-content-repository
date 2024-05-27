<?php

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Service\ContentRepositoryBootstrapper;

require __DIR__ . '/vendor/autoload.php';

$contentRepository = App\Common::getContentRepository();

$rootNodeTypeName = NodeTypeName::fromString('MyProject:Root');

$bootstrapper = ContentRepositoryBootstrapper::create($contentRepository);
$liveWorkspace = $bootstrapper->getOrCreateLiveWorkspace();
echo "🟢 finished setting up live workspace / content stream\n";
$rootNodeIdentifier = $bootstrapper->getOrCreateRootNodeAggregate(
    $liveWorkspace,
    $rootNodeTypeName
);

echo "🟢 finished setting up root node\n";

\App\NodeTreePrinter::print($contentRepository);
