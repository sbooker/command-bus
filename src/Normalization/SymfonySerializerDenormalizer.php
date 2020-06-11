<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Normalization;

use Sbooker\CommandBus\Denormalizer;
use Sbooker\CommandBus\NameGiver;
use Sbooker\CommandBus\NormalizedCommand;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class SymfonySerializerDenormalizer implements Denormalizer
{
    /** @var NameGiver  */
    private $nameGiver;

    /** @var DenormalizerInterface */
    private $denormalizer;

    public function __construct(NameGiver $nameGiver, DenormalizerInterface $denormalizer)
    {
        $this->nameGiver = $nameGiver;
        $this->denormalizer = $denormalizer;
    }

    public function denormalize(?array $data, string $name): ?object
    {
        return $this->denormalizer->denormalize($data, $this->nameGiver->getClass($name), 'json');
    }
}