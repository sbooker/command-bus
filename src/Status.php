<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Sbooker\Workflow\EnumTrait;

enum Status: string implements \Sbooker\Workflow\Status
{
    use EnumTrait;

    case created = 'created';
    case pending = 'pending';
    case success = 'success';
    case fail = 'fail';
}