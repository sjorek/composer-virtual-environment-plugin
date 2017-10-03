<?php
namespace Sjorek\Composer\ShellPlugin\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ActivateEnvironmentCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('activate-environment');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Executing');
    }
}

