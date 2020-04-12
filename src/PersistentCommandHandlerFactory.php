<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Sbooker\TransactionManager\TransactionManager;

final class PersistentCommandHandlerFactory
{
    private Registry $registry;

    private Denormalizer $denormalizer;

    private WriteStorage $storage;

    private TransactionManager $transactionManager;

    public function __construct(Registry $registry, Denormalizer $denormalizer, WriteStorage $storage, TransactionManager $transactionManager)
    {
        $this->registry = $registry;
        $this->denormalizer = $denormalizer;
        $this->storage = $storage;
        $this->transactionManager = $transactionManager;
    }

    public function create(array $names): PersistentCommandHandler
    {
        return
            new PersistentCommandHandler(
                $this->registry,
                $this->denormalizer,
                $this->storage,
                $this->transactionManager,
                $names
            );
    }
}