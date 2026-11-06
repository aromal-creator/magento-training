<?php

namespace Codilar\ConfigCache\Console\Command;

use Codilar\ConfigCache\Model\ConfigCache;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento codilar:config-cache:show --store=<id>
 *
 * Prints a store view's cached values and whether they came from the cache (HIT) or config (MISS).
 */
class ShowCommand extends Command
{
    private const OPTION_STORE = 'store';

    private ConfigCache $configCache;
    private StoreManagerInterface $storeManager;

    public function __construct(ConfigCache $configCache, StoreManagerInterface $storeManager)
    {
        $this->configCache = $configCache;
        $this->storeManager = $storeManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('codilar:config-cache:show')
            ->setDescription('Show the cached store configuration values for a store view')
            ->addOption(self::OPTION_STORE, 's', InputOption::VALUE_REQUIRED, 'Store view ID', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $store = $this->storeManager->getStore((int)$input->getOption(self::OPTION_STORE));
        } catch (NoSuchEntityException $e) {
            $output->writeln('<error>Store view not found.</error>');
            return Cli::RETURN_FAILURE;
        }

        $storeId = (int)$store->getId();
        // Checked before get(), because get() fills the cache on a miss
        $wasCached = $this->configCache->isCached($storeId);
        $data = $this->configCache->get($storeId);

        $output->writeln(sprintf('Store view: %s (ID %d)', $store->getName(), $storeId));
        $output->writeln('Cache key:  ' . $this->configCache->getCacheId($storeId));
        $output->writeln('Result:     ' . ($wasCached ? 'HIT (from cache)' : 'MISS (read from config, now cached)'));
        $output->writeln('Cached at:  ' . date('Y-m-d H:i:s', $data['cached_at']));
        $output->writeln('Expires at: ' . date('Y-m-d H:i:s', $data['cached_at'] + ConfigCache::CACHE_LIFETIME));
        foreach ($data['values'] as $label => $value) {
            $output->writeln(sprintf('  %-12s %s', $label . ':', $value !== '' ? $value : '(not set)'));
        }

        return Cli::RETURN_SUCCESS;
    }
}
