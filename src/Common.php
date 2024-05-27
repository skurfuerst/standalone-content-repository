<?php

namespace App;

use Doctrine\DBAL\DriverManager;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Symfony\Component\Yaml\Yaml;

class Common
{
    private static ?\Doctrine\DBAL\Connection $connection = null;
    private static ?StandaloneContentRepositoryRegistry $registry = null;

    public static function getConnection(): \Doctrine\DBAL\Connection
    {
        if (!self::$connection) {
            $connectionParams = [
                'dbname' => 'db',
                'user' => 'db',
                'password' => 'db',
                'host' => '127.0.0.1',
                'driver' => 'pdo_mysql',
                'port' => '23306'
            ];

            self::$connection = DriverManager::getConnection($connectionParams);
            self::$connection->connect();
        }

        return self::$connection;
    }

    public static function getContentRepository(): ContentRepository
    {
        if (!self::$registry) {
            self::$registry = new \App\StandaloneContentRepositoryRegistry(
                self::getConnection(),
                dimensionConfiguration: [],
                nodeTypeConfiguration: Yaml::parse(file_get_contents('NodeTypes.yaml') ?: throw new \RuntimeException('Failed to read NodeType schema.')),
                additionalProjectionFactories: []
            );

        }

        $contentRepository = self::$registry->get(ContentRepositoryId::fromString('default'));
        $contentRepository->setUp();

        return $contentRepository;
    }

    public static function getRootNodeId(): NodeAggregateId
    {
        $contentRepository = self::getContentRepository();
        $rootNodeTypeName = NodeTypeName::fromString('MyProject:Root');

        $liveContentGraph = $contentRepository->getContentGraph(WorkspaceName::forLive());
        $rootNodeAggregate = $liveContentGraph->findRootNodeAggregateByType($rootNodeTypeName);
        return $rootNodeAggregate->nodeAggregateId;
    }
}
