<?php

declare(strict_types=1);

namespace Tests\Sbooker\CommandBus\Infrastructure\Persistence;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Ramsey\Uuid\Doctrine\UuidType;
use Sbooker\CommandBus\Infrastructure\Persistence\Mapping\StatusType;

final class EntityManagerBuilder
{
    private static ?EntityManagerBuilder $me = null;

    private Configuration $configuration;

    /** @var EntityManager[] */
    private array $ems = [];

    private function __construct()
    {
        $this->configuration = $this->buildConfiguration();
    }

    private function buildConfiguration(): Configuration
    {
        $configuration = new Configuration();
        $configuration->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $configuration->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $configuration->setProxyDir(__DIR__ . '/Proxies');
        $configuration->setProxyNamespace('Test\Sbooker\CommandBus\Infrastructure\Persistence\Proxies');
        $configuration->setAutoGenerateProxyClasses(true);
        $configuration->setMetadataDriverImpl(
            new SimplifiedXmlDriver([
                __DIR__ . '/../../../src/Infrastructure/Persistence/Mapping' => 'Sbooker\CommandBus',
            ])
        );
        $configuration->setNamingStrategy(new UnderscoreNamingStrategy());

        return $configuration;
    }

    public static function me(): self
    {
        if (null === self::$me) {
            self::$me = new self();
            Type::addType('uuid', UuidType::class);
            Type::addType('command_status', StatusType::class);
        }

        return self::$me;
    }

    public function get(TestDatabases $db): EntityManager
    {
        if (!isset($this->ems[$db->getRawValue()])) {
            $this->ems[$db->getRawValue()] = EntityManager::create(self::getParams($db), $this->configuration);
        }

        return $this->ems[$db->getRawValue()];
    }

    private static function getParams(TestDatabases $db): array
    {
        switch ($db) {
            case TestDatabases::postgresql12():
                $params = [
                    'driver' => 'pdo_pgsql',
                    'host' => 'pgsql12',
                    'port' => 5432,
                    'server_version' => '12',
                ];
                break;
            case TestDatabases::mysql5():
                $params = [
                    'driver' => 'pdo_mysql',
                    'host' => 'mysql5',
                    'port' => 3306,
                    'server_version' => '5',
                ];
                break;
            case TestDatabases::mysql8():
                $params = [
                    'driver' => 'pdo_mysql',
                    'host' => 'mysql8',
                    'port' => 3306,
                    'server_version' => '8',
                ];
                break;
            default:
                throw new \InvalidArgumentException("No config for db $db");
        }

        return
            array_merge(
                [
                    'user' => 'user',
                    'password' => 'password',
                    'dbname' => 'test',
                    'charset' =>  'utf8',
                ],
                $params
            );
    }

    private function __sleep() {}
    private function __wakeup() {}
    private function __clone() {}
}