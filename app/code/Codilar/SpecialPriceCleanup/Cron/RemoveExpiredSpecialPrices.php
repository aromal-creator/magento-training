<?php

namespace Codilar\SpecialPriceCleanup\Cron;

use Codilar\SpecialPriceCleanup\Model\ExpiredSpecialPriceRemover;
use Psr\Log\LoggerInterface;

/**
 * Cron job (etc/crontab.xml): removes expired special prices and logs how many products changed.
 */
class RemoveExpiredSpecialPrices
{
    private ExpiredSpecialPriceRemover $remover;
    private LoggerInterface $logger;

    public function __construct(ExpiredSpecialPriceRemover $remover, LoggerInterface $logger)
    {
        $this->remover = $remover;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $productIds = $this->remover->execute();

        // Written to var/log/system.log
        $this->logger->info(sprintf(
            'SpecialPriceCleanup: removed expired special price from %d product(s).%s',
            count($productIds),
            $productIds ? ' IDs: ' . implode(', ', $productIds) : ''
        ));
    }
}
