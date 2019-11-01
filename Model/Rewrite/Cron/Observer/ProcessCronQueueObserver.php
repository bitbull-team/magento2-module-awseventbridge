<?php
namespace Bitbull\AWSEventBridge\Model\Rewrite\Cron\Observer;

use \Magento\Cron\Observer\ProcessCronQueueObserver as BaseProcessCronQueueObserver;
use \Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\Profiler\Driver\Standard\StatFactory;

class ProcessCronQueueObserver extends BaseProcessCronQueueObserver
{
    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Cron\Model\ScheduleFactory $scheduleFactory
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Cron\Model\ConfigInterface $config
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Console\Request $request
     * @param \Magento\Framework\ShellInterface $shell
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\State $state
     * @param StatFactory $statFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Cron\Model\ScheduleFactory $scheduleFactory,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Cron\Model\ConfigInterface $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Console\Request $request,
        \Magento\Framework\ShellInterface $shell,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\State $state,
        StatFactory $statFactory,
        \Magento\Framework\Lock\LockManagerInterface $lockManager,
        EventManager $eventManager
    ) {
        parent::__construct(
            $objectManager,
            $scheduleFactory,
            $cache,
            $config,
            $scopeConfig,
            $request,
            $shell,
            $dateTime,
            $phpExecutableFinderFactory,
            $logger,
            $state,
            $statFactory,
            $lockManager
        );
        $this->eventManager = $eventManager;
    }

    /**
     * @inheritDoc
     */
    protected function _runJob($scheduledTime, $currentTime, $jobConfig, $schedule, $groupId) {
        $trackingJobData = [
            'scheduledTime' => $scheduledTime,
            'currentTime' => $currentTime,
            'jobConfig' => $jobConfig,
            'groupId' => $groupId,
            'code' => $schedule->getJobCode()
        ];

        $this->eventManager->dispatch('cron_job_start', $trackingJobData);
        $start = microtime(true);
        try {
            parent::_runJob($scheduledTime, $currentTime, $jobConfig, $schedule, $groupId);
        } catch (\Exception $e) {
            $trackingJobData['error'] = $e;
            $this->eventManager->dispatch('cron_job_error', $trackingJobData);
            throw  $e;
        }
        $timeElapsedSecs = round(microtime(true) - $start, 3);
        $trackingJobData['duration'] = $timeElapsedSecs;
        $this->eventManager->dispatch('cron_job_success', $trackingJobData);
    }
}
