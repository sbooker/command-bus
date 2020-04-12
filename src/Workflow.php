<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Ds\Map;
use Ds\Set;

/**
 * @internal
 * @method Status getStatus()
 */
final class Workflow extends \Sbooker\Workflow\Workflow
{
    public function __construct()
    {
        parent::__construct(Status::created());
    }

    protected function buildTransitionMap(): Map
    {
        $map = new Map();

        $map->put(Status::created(), new Set([Status::pending()]));
        $map->put(Status::pending(), new Set([Status::pending(), Status::fail(), Status::success()]));

        return $map;
    }

    protected function getStatusClass(): string
    {
        return Status::class;
    }
}