<?php

declare(strict_types=1);

namespace Tests\Sbooker\CommandBus\Infrastructure\Persistence;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\ReadStorage;
use Sbooker\CommandBus\WriteStorage;
use Sbooker\TransactionManager\DoctrineTransactionHandler;
use Sbooker\TransactionManager\TransactionManager;

class DoctrineRepositoryTest extends StorageTest
{
    /** @var SchemaTool */
    private $schemaTool;

    /** @var EntityManager */
    private $em;

    protected function getReadStorage(): ReadStorage
    {
        return $this->em->getRepository(Command::class);
    }

    protected function getWriteStorage(): WriteStorage
    {
        return $this->em->getRepository(Command::class);
    }

    protected function setUpDbDeps(TestDatabases $db): void
    {
        $this->em = $em = EntityManagerBuilder::me()->get($db);
        $this->schemaTool = new SchemaTool($em);
        $this->transactionManager = new TransactionManager(new DoctrineTransactionHandler($em));
        $this->schemaTool->dropSchema($this->getMetadata($em));
        $this->schemaTool->createSchema($this->getMetadata($em));
    }

    protected function tearDownDbDeps(): void
    {
        $this->schemaTool->dropSchema($this->getMetadata($this->em));
        $this->em = null;
    }

    private function getMetadata(EntityManager $em)
    {
        return $em->getMetadataFactory()->getAllMetadata();
    }
}