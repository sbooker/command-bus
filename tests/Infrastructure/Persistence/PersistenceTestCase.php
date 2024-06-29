<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Tests\Infrastructure\Persistence;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Ramsey\Uuid\UuidInterface;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\Infrastructure\Persistence\DoctrineRepository;
use Sbooker\CommandBus\NormalizedCommand;
use Sbooker\CommandBus\Normalizer;
use Sbooker\TransactionManager\DoctrineTransactionHandler;
use Sbooker\TransactionManager\TransactionManager;
use Sbooker\CommandBus\Tests\TestCase;

abstract class PersistenceTestCase extends TestCase
{
    private SchemaTool $schemaTool;

    private TransactionManager $transactionManager;

    final protected function tearDownDbDeps(EntityManager $em): void
    {
        $this->schemaTool->dropSchema($this->getMetadata($em));
        $this->em = null;
    }

    final protected function getMetadata(EntityManager $em)
    {
        return $em->getMetadataFactory()->getAllMetadata();
    }

    final protected function getRepository(EntityManager $em): DoctrineRepository
    {
        return $em->getRepository(Command::class);
    }

    final protected function getTransactionManager(): TransactionManager
    {
        return $this->transactionManager;
    }

    final protected function createCommand(UuidInterface $commandId, string $commandName, string $nextAttemptAt = 'now'): Command
    {
        $command = new Command($commandId, new \stdClass(), $this->createNormalizer($commandName));
        $workflow = $this->getPrivatePropertyValue($command, 'attemptCounter');
        $this->openProperty($workflow, 'nextAttemptAt')->setValue($workflow, new \DateTimeImmutable($nextAttemptAt));

        return $command;
    }

    final protected function makeFixtures(object ... $objects): void
    {
        $this->getTransactionManager()->transactional(function () use ($objects) {
            foreach ($objects as $object) {
                $this->getTransactionManager()->persist($object);
            }
        });
    }

    private function createNormalizer(string $commandName): Normalizer
    {
        return new class ($commandName) implements Normalizer {
            private string $commandName;

            public function __construct(string $commandName)
            {
                $this->commandName = $commandName;
            }

            public function normalize(object $command): NormalizedCommand
            {
                return new NormalizedCommand($this->commandName, null);
            }
        };
    }

    final protected function setUpDbDeps(string $db): EntityManager
    {
        $em = EntityManagerBuilder::me()->get($db);
        $this->schemaTool = new SchemaTool($em);
        $this->transactionManager = new TransactionManager(new DoctrineTransactionHandler($em));
        $this->schemaTool->dropSchema($this->getMetadata($em));
        $this->schemaTool->createSchema($this->getMetadata($em));

        return $em;
    }

    public function dbs(): array
    {
        return [
            [ EntityManagerBuilder::PGSQL12 ],
            [ EntityManagerBuilder::MYSQL8 ],
        ];
    }
}