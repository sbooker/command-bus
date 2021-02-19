<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Ramsey\Uuid\UuidInterface;

/* final */ class Command
{
    private UuidInterface $id;

    private Workflow $workflow;

    private AttemptCounter $attemptCounter;

    private NormalizedCommand $normalizedCommand;

    private ?array $result;

    public function __construct(UuidInterface $id, object $payload, Normalizer $normalizer)
    {
        $this->id = $id;
        $this->workflow = new Workflow();
        $this->attemptCounter = new AttemptCounter();
        $this->normalizedCommand = $normalizer->normalize($payload);
        $this->result = null;
    }

    /**
     * @throws \Sbooker\Workflow\FlowError
     */
    public function execute(Registry $registry, Denormalizer $denormalizer): void
    {
        try {
            $this->transitTo(Status::pending());
            $this->attemptCounter->nextAttempt($registry->getTimeoutCalculator($this->getName()));

            $registry->getEndpoint($this->getName())
                ->process(
                    $this->id,
                    $this->normalizedCommand->denormalizeWith($denormalizer)
                );

            $this->registerComplete($registry->getOnSuccessInvoker($this->getName()));
        } catch (MaxAttemptReached $exception) {
            $this->registerFail($registry->getOnFailInvoker($this->getName()));
        } catch (\Exception $exception) {
            $this->result = [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'class' => get_class($exception),
                'trace' => $exception->getTraceAsString(),
            ];
        }
    }

    public function getState(): State
    {
        return new State($this->workflow->getStatus(), $this->result);
    }

    /**
     * @throws \Sbooker\Workflow\FlowError
     */
    private function registerFail(?callable $callback): void
    {
        $this->finalize(Status::fail(), $callback);
    }

    /**
     * @throws \Sbooker\Workflow\FlowError
     */
    private function registerComplete(?callable $callback): void
    {
        $this->finalize(Status::success(), $callback);
    }

    /**
     * @throws \Sbooker\Workflow\FlowError
     */
    private function finalize(Status $finalStatus, ?callable $callback): void
    {
        $this->transitTo($finalStatus);
        if (null !== $callback) {
            $callback($this->id, $this->getName(), $this->result);
        }
    }

    /**
     * @throws \Sbooker\Workflow\FlowError
     */
    private function transitTo(Status $status): void
    {
        $this->workflow->transitTo($status);
    }

    private function getName(): string
    {
        return $this->normalizedCommand->getName();
    }
}