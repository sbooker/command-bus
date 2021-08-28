<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Ramsey\Uuid\UuidInterface;
use Sbooker\TransactionManager\TransactionManager;

final class PersistentCommandHandler implements Handler
{
    private Registry $registry;

    private Denormalizer $denormalizer;

    private WriteStorage $storage;

    private TransactionManager $transactionManager;

    /** @var string[] */
    private array $names;

    public function __construct(
        Registry $registry,
        Denormalizer $denormalizer,
        WriteStorage $storage,
        TransactionManager $transactionManager,
        array $names = []
    ) {
        $this->registry = $registry;
        $this->denormalizer = $denormalizer;
        $this->storage = $storage;
        $this->transactionManager = $transactionManager;
        $this->names = $names;
    }

    public function handleNext(): bool
    {
        return
            $this->transactionManager->transactional(function (): bool {
                $command = $this->storage->getFirstToProcessAndLock($this->names);
                if (null === $command) {
                    return false;
                }

                $command->execute($this->registry, $this->denormalizer);

                $this->transactionManager->save($command);

                return true;
            });
    }

    public function handle(UuidInterface $id): void
    {
        $this->transactionManager->transactional(function () use ($id): void {
            $command = $this->storage->getAndLock($this->names, $id);
            if (null === $command) {
                return;
            }

            $command->execute($this->registry, $this->denormalizer);

            $this->transactionManager->save($command);
        });
    }
}