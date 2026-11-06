<?php

namespace Codilar\CronDemo\Cron;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Demo cron job: writes a line to var/log/system.log every time it runs.
 */
class Heartbeat
{
    private LoggerInterface $logger;
    private DateTime $dateTime;

    public function __construct(LoggerInterface $logger, DateTime $dateTime)
    {
        $this->logger = $logger;
        $this->dateTime = $dateTime;
    }

    public function execute(): void
    {
        $this->logger->info('CronDemo: heartbeat at ' . $this->dateTime->gmtDate() . ' (UTC)');
    }
}
