<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Ramsey\Uuid\UuidInterface;
use Sbooker\TransactionManager\TransactionManager;

final class PersistentCommandBus implements CommandBus
{
    private Normalizer $normalizer;

    private TransactionManager $transactionManager;

    private ReadStorage $readStorage;

    public function __construct(
        Normalizer $normalizer,
        TransactionManager $transactionManager,
        ReadStorage $readStorage
    ) {
        $this->normalizer = $normalizer;
        $this->transactionManager = $transactionManager;
        $this->readStorage = $readStorage;
    }


    public function accept(UuidInterface $id, object $command): void
    {
        if (null !== $this->readStorage->get($id)) {
            return;
        }

        $persistentCommand = new Command($id, $command, $this->normalizer);

        $this->transactionManager->transactional(function () use ($persistentCommand): void {
            $this->transactionManager->persist($persistentCommand);
        });
    }

    public function getState(UuidInterface $id): ?State
    {
        $command = $this->readStorage->get($id);
        if (null !== $command) {
            return $command->getState();
        }

        return null;
    }
}