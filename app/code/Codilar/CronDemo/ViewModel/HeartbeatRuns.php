<?php

namespace Codilar\CronDemo\ViewModel;

use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\Schedule;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Reads the heartbeat job's rows from Magento's cron_schedule table.
 */
class HeartbeatRuns implements ArgumentInterface
{
    public const JOB_CODE = 'codilar_cron_demo_heartbeat';
    private const LIMIT = 10;

    private CollectionFactory $scheduleCollectionFactory;

    public function __construct(CollectionFactory $scheduleCollectionFactory)
    {
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
    }

    /**
     * Newest runs first, including the planned (pending) ones.
     *
     * @return Schedule[]
     */
    public function getRuns(): array
    {
        $collection = $this->scheduleCollectionFactory->create()
            ->addFieldToFilter('job_code', self::JOB_CODE)
            ->setOrder('scheduled_at', 'DESC')
            ->setPageSize(self::LIMIT);

        return $collection->getItems();
    }

    public function getLastSuccess(): ?Schedule
    {
        $collection = $this->scheduleCollectionFactory->create()
            ->addFieldToFilter('job_code', self::JOB_CODE)
            ->addFieldToFilter('status', Schedule::STATUS_SUCCESS)
            ->setOrder('finished_at', 'DESC')
            ->setPageSize(1);

        $schedule = $collection->getFirstItem();
        return $schedule->getId() ? $schedule : null;
    }
}
