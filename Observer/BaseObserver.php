<?php declare(strict_types=1);

namespace Bitbull\Mimmo\Observer;

use Bitbull\Mimmo\Api\Service\ConfigInterface;
use Bitbull\Mimmo\Api\Service\LoggerInterface;
use Bitbull\Mimmo\Model\Service\EventEmitter;
use Bitbull\Mimmo\Api\ObserverInterface;
use Magento\Framework\Event\Observer;

abstract class BaseObserver implements ObserverInterface
{
    const EVENT_NAME = null;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var EventEmitter
     */
    protected $eventEmitter;

    /**
     * @param LoggerInterface $logger
     * @param ConfigInterface $config
     * @param EventEmitter $eventEmitter
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigInterface $config,
        EventEmitter $eventEmitter
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->eventEmitter = $eventEmitter;
    }

    /**
     * @inheritDoc
     */
    public function getEventName(){
        $className = null;
        try{
            $reflectionClass = new \ReflectionClass($this);
            if ($reflectionClass->getShortName() === 'Interceptor') {
                $reflectionClass = $reflectionClass->getParentClass();
            }
            $className = $reflectionClass->getConstant('EVENT_NAME');
            if ($className === null || $className === false) {
                $namespaces = explode('\\', $reflectionClass->getNamespaceName());
                $className = array_pop($namespaces).$reflectionClass->getShortName();
            }
        }catch (\ReflectionException $error) {
            $this->logger->logException($error);
        }
        return $className;
    }

    /**
     * @inheritDoc
     */
    abstract public function execute(Observer $observer);
}
