<?php

namespace App;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainerFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Symfony\Component\Yaml\Yaml;

class Common
{
    private static ?Connection $connection = null;
    private static ?StandaloneContentRepositoryRegistry $registry = null;

    public static function getConnection(): Connection
    {
        if (!self::$connection) {
            $connectionParams = [
                'dbname' => 'cr-stand-alone',
                'user' => 'neos',
                'password' => '123456',
                'host' => '127.0.0.1',
                'driver' => 'pdo_mysql',
                'port' => 3306
            ];

            self::$connection = DriverManager::getConnection($connectionParams);
            self::$connection->connect();
        }

        return self::$connection;
    }

    public static function getContentRepository(): ContentRepository
    {
        $registry = self::getContentRepositoryRegistry();

        return $registry->get(ContentRepositoryId::fromString('default'));
    }

    public static function getContentRepositoryMaintainer(): ContentRepositoryMaintainer
    {
        $registry = self::getContentRepositoryRegistry();

        return $registry->buildService(ContentRepositoryId::fromString('default'), new ContentRepositoryMaintainerFactory());
    }

    private static function getContentRepositoryRegistry(): StandaloneContentRepositoryRegistry
    {
        return self::$registry ??= new \App\StandaloneContentRepositoryRegistry(
            self::getConnection(),
            dimensionConfiguration: [],
            nodeTypeConfiguration: Yaml::parse(file_get_contents('NodeTypes.yaml') ?: throw new \RuntimeException('Failed to read NodeType schema.')),
        );
    }
}
