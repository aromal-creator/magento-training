<?php

namespace Codilar\StoreConfigCache\Console\Command;

use Codilar\StoreConfigCache\Model\StoreConfigCache;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento codilar:store-config:clear
 */
class ClearCommand extends Command
{
    private StoreConfigCache $storeConfigCache;

    public function __construct(StoreConfigCache $storeConfigCache)
    {
        $this->storeConfigCache = $storeConfigCache;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('codilar:store-config:clear')
            ->setDescription('Clear the Codilar store config cache for all store views');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->storeConfigCache->clean();
        $output->writeln('<info>Codilar store config cache cleared for all store views.</info>');

        return Cli::RETURN_SUCCESS;
    }
}
