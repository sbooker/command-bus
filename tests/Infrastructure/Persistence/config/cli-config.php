<?php

declare(strict_types=1);

require_once 'bootstrap.php';

return
    Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet(
        \Tests\Sbooker\CommandBus\Infrastructure\Persistence\EntityManagerBuilder::me()->get('pgsql12')
    );
