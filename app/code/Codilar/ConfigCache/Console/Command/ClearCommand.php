<?php

namespace Codilar\ConfigCache\Console\Command;

use Codilar\ConfigCache\Model\ConfigCache;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento codilar:config-cache:clear
 *
 * Removes the cached values of every store view; the next read fills the cache again.
 */
class ClearCommand extends Command
{
    private ConfigCache $configCache;

    public function __construct(ConfigCache $configCache)
    {
        $this->configCache = $configCache;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('codilar:config-cache:clear')
            ->setDescription('Clear the Codilar config cache for all store views');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->configCache->clean();
        $output->writeln('<info>Codilar config cache cleared for all store views.</info>');

        return Cli::RETURN_SUCCESS;
    }
}
