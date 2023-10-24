<?php

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;

require __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Service\ContentRepositoryBootstrapper;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Symfony\Component\Yaml\Yaml;

$connectionParams = [
    'dbname' => 'db',
    'user' => 'db',
    'password' => 'db',
    'host' => '127.0.0.1',
    'driver' => 'pdo_mysql',
    'port' => '13306'
];
$connection = DriverManager::getConnection($connectionParams);
$connection->connect();


$registry = new \App\StandaloneContentRepositoryRegistry(
    $connection,
    dimensionConfiguration: [],
    nodeTypeConfiguration: Yaml::parse(file_get_contents('NodeTypes.yaml')),
    additionalProjectionFactories: []
);

$contentRepository = $registry->get(ContentRepositoryId::fromString('default'));
$contentRepository->setUp();

$connection->executeStatement('TRUNCATE cr_default_events');
$contentRepository->resetProjectionStates();

$rootNodeTypeName = NodeTypeName::fromString('MyProject:Root');

$bootstrapper = ContentRepositoryBootstrapper::create($contentRepository);
$liveContentStreamId = $bootstrapper->getOrCreateLiveContentStream();
$rootNodeIdentifier = $bootstrapper->getOrCreateRootNodeAggregate(
    $liveContentStreamId,
    $rootNodeTypeName
);

$contentRepository->handle(
    new CreateNodeAggregateWithNode(
        contentStreamId: $liveContentStreamId,
        nodeAggregateId: NodeAggregateId::create(),
        nodeTypeName: NodeTypeName::fromString('MyProject:Page'),
        originDimensionSpacePoint: OriginDimensionSpacePoint::fromArray([]),
        parentNodeAggregateId: $rootNodeIdentifier,
        initialPropertyValues: PropertyValuesToWrite::fromArray([
            'title' => 'My Title',
//            'description' => 'description'
        ])
    )
)->block();

$subgraph = $contentRepository->getContentGraph()->getSubgraph(
    $liveContentStreamId,
    DimensionSpacePoint::fromArray([]),
    VisibilityConstraints::frontend()
);

var_dump(iterator_to_array($subgraph->findRootNodeByType($rootNodeTypeName)->properties->getIterator()));
