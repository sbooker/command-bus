<?php

namespace Sbooker\CommandBus\Infrastructure\Console;

use Sbooker\CommandBus\CommandBusCleaner;
use Sbooker\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Clean extends Command
{
    private CommandBusCleaner $cleaner;

    public function __construct(CommandBusCleaner $cleaner)
    {
        parent::__construct();
        $this->cleaner = $cleaner;
    }

    protected function configure()
    {
        $this->setDescription('Clean used commands in bus.');
        $this->addArgument('success', InputArgument::OPTIONAL, "DateTime <https://www.php.net/manual/en/datetime.formats.php> to which all SUCCESS commands will be deleted");
        $this->addArgument('failed', InputArgument::OPTIONAL, "DateTime <https://www.php.net/manual/en/datetime.formats.php> to which all FAILED commands will be deleted");
    }

    protected function doExecute(InputInterface $input, OutputInterface $output): void
    {
        $this->cleaner->clean($this->resolveArgument($input, 'success'), $this->resolveArgument($input, 'failed'));
    }

    private function resolveArgument(InputInterface $input, string $name): ?\DateTimeImmutable
    {
        $argument = $input->getArgument($name);

        return (null === $argument) ? null : new \DateTimeImmutable($argument);
    }
}