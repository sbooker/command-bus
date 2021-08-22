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
    public const PGSQL12 = 'pgsql12';
    public const MYSQL5 = 'mysql5';
    public const MYSQL8 = 'mysql8';

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

    public function get(string $db): EntityManager
    {
        if (!isset($this->ems[$db])) {
            $this->ems[$db] = EntityManager::create(self::getParams($db), $this->configuration);
        }

        return $this->ems[$db];
    }

    private static function getParams(string $db): array
    {
        switch ($db) {
            case self::PGSQL12:
                $params = [
                    'driver' => 'pdo_pgsql',
                    'host' => self::PGSQL12,
                    'port' => 5432,
                    'server_version' => '12',
                ];
                break;
            case self::MYSQL5:
                $params = [
                    'driver' => 'pdo_mysql',
                    'host' => self::MYSQL5,
                    'port' => 3306,
                    'server_version' => '5',
                ];
                break;
            case self::MYSQL8:
                $params = [
                    'driver' => 'pdo_mysql',
                    'host' => self::MYSQL8,
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

    final public function __clone()
    {
        throw new \BadMethodCallException('Cloning is restricted for enumerable types');
    }

    final public function __sleep()
    {
        throw new \BadMethodCallException('Serialization is restricted for enumerable types');
    }

    final public function __wakeup()
    {
        throw new \BadMethodCallException('Serialization is restricted for enumerable types');
    }
}