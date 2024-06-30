<?php

namespace Sbooker\CommandBus;

final class PersistentCommandBusCleaner implements CommandBusCleaner
{
    private CleanStorage $storage;
    private ?\DateTimeImmutable $successBefore;
    private ?\DateTimeImmutable $failedBefore;

    public function __construct(CleanStorage $storage, ?\DateTimeImmutable $successBefore, ?\DateTimeImmutable $failedBefore)
    {
        $this->storage = $storage;
        $this->successBefore = $successBefore;
        $this->failedBefore = $failedBefore;
    }

    public function clean(?\DateTimeImmutable $successBefore = null, ?\DateTimeImmutable $failedBefore = null): void
    {
        $successBefore = $this->selectFrom($successBefore, $this->successBefore);
        if (null !== $successBefore) {
            $this->storage->cleanSuccessCommands($successBefore);
        }
        $failedBefore = $this->selectFrom($failedBefore, $this->failedBefore);
        if (null !== $failedBefore) {
            $this->storage->cleanFailedCommands($failedBefore);
        }
    }

    private function selectFrom(?\DateTimeImmutable $first, ?\DateTimeImmutable $second):?\DateTimeImmutable
    {
        if (null !== $first) {
            return $first;
        }

        return $second;
    }
}