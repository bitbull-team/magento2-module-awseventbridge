<?php
namespace Bitbull\Mimmo\Plugin;

use Bitbull\Mimmo\Api\Service\ConfigInterface;
use Bitbull\Mimmo\Api\Service\LoggerInterface;
use Bitbull\Mimmo\Api\ObserverInterface;

class Observer
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @param LoggerInterface $logger
     * @param ConfigInterface $config
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigInterface $config
    ) {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @param ObserverInterface $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundExecute(ObserverInterface $subject, callable $proceed, \Magento\Framework\Event\Observer $observer)
    {
        $eventName = $subject->getEventName();
        if ($this->config->isEventEnabled($eventName) === false) {
            $this->logger->debug("Event '$eventName' disabled, skipping emitter");
            return null;
        }

        $this->logger->debug("Event '$eventName' captured, emitting..");
        $start = microtime(true);
        try{
            $result = $proceed($observer);
        }catch (\Exception $exception) {
            $this->logger->logException($exception);
            return null;
        }
        $timeElapsedSecs = round(microtime(true) - $start, 3);
        $this->logger->debug("Event '$eventName' emitted in ${timeElapsedSecs}s");
        return $result;
    }
}
