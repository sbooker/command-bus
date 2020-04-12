<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Ramsey\Uuid\UuidInterface;
use Sbooker\CommandBus\CommandBus;
use Sbooker\TransactionManager\TransactionManager;

final class PersistentCommandCommandBus implements CommandBus
{
    private Normalizer $normalizer;

    private WriteStorage $writeStorage;

    private TransactionManager $transactionManager;

    private ReadStorage $readStorage;

    public function __construct(
        Normalizer $normalizer,
        WriteStorage $writeStorage,
        TransactionManager $transactionManager,
        ReadStorage $readStorage
    ) {
        $this->normalizer = $normalizer;
        $this->writeStorage = $writeStorage;
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
            $this->writeStorage->add($persistentCommand);
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