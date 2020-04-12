<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Infrastructure\Console;

use Ramsey\Uuid\Uuid;
use Sbooker\CommandBus\Handler;
use Sbooker\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ExecuteCommand extends Command
{
    private Handler $handler;

    public function __construct(Handler $handler)
    {
        parent::__construct();
        $this->handler = $handler;
    }

    protected function configure()
    {
        $this->setDescription('Execute single command from bus. ');
        $this->addArgument('id', InputArgument::OPTIONAL, 'Command Id <UUID>. If not present execute first scheduled command');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output): void
    {
        $id = $input->getArgument('id');

        if (null === $id) {
            $this->handler->handleNext();
            return;
        }

        $this->handler->handle(Uuid::fromString($id));
    }
}