<?php

namespace Codilar\SpecialPriceCleanup\Console\Command;

use Codilar\SpecialPriceCleanup\Cron\RemoveExpiredSpecialPrices;
use Codilar\SpecialPriceCleanup\Model\ExpiredSpecialPriceRemover;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento codilar:special-price:cleanup [--dry-run]
 *
 * Runs the same job as the cron, on demand.
 */
class CleanupCommand extends Command
{
    private const OPTION_DRY_RUN = 'dry-run';

    private RemoveExpiredSpecialPrices $cronJob;
    private ExpiredSpecialPriceRemover $remover;

    public function __construct(RemoveExpiredSpecialPrices $cronJob, ExpiredSpecialPriceRemover $remover)
    {
        $this->cronJob = $cronJob;
        $this->remover = $remover;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('codilar:special-price:cleanup')
            ->setDescription('Remove special prices whose special_to_date has passed')
            ->addOption(self::OPTION_DRY_RUN, null, InputOption::VALUE_NONE, 'Only list the products, change nothing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption(self::OPTION_DRY_RUN)) {
            $productIds = $this->remover->findExpiredProductIds();
            $output->writeln(sprintf(
                'Dry run: %d product(s) have an expired special price.%s',
                count($productIds),
                $productIds ? ' IDs: ' . implode(', ', $productIds) : ''
            ));
            return Cli::RETURN_SUCCESS;
        }

        $this->cronJob->execute();
        $output->writeln('<info>Done. The result is logged in var/log/system.log.</info>');

        return Cli::RETURN_SUCCESS;
    }
}
