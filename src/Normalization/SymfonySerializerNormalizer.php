<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Normalization;

use Sbooker\CommandBus\Normalizer;
use Sbooker\CommandBus\NameGiver;
use Sbooker\CommandBus\NormalizedCommand;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class SymfonySerializerNormalizer implements Normalizer
{
    private NameGiver $nameGiver;

    private NormalizerInterface $normalizer;

    public function __construct(NameGiver $nameGiver, NormalizerInterface $normalizer)
    {
        $this->nameGiver = $nameGiver;
        $this->normalizer = $normalizer;
    }

    public function normalize(object $command): NormalizedCommand
    {
        return
            new NormalizedCommand(
                $this->nameGiver->getName($command),
                $this->normalizer->normalize($command, 'json')
            );
    }
}