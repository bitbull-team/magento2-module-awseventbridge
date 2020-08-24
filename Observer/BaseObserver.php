<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Observer;

use Bitbull\AWSEventBridge\Api\ObserverInterface;
use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Bitbull\AWSEventBridge\Model\Service\EventEmitter;
use Magento\Framework\Event\Observer;
use ReflectionClass;

abstract class BaseObserver implements ObserverInterface
{
    const SCOPE_NAME = null; // default is last namespace
    const EVENT_NAME = null; // default is class name

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
     * @var ReflectionClass
     */
    protected $reflectionClass;

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
        $this->reflectionClass = null;
    }

    /**
     * Get reflation class
     *
     * @return ReflectionClass|null
     */
    private function getReflectionClass()
    {
        if ($this->reflectionClass  !== null) {
            return $this->reflectionClass;
        }
        try {
            $this->reflectionClass =new ReflectionClass($this);
        } catch (\ReflectionException $error) {
            $this->logger->logException($error);
            return null;
        }
        if ($this->reflectionClass->getShortName() === 'Interceptor') {
            $this->reflectionClass = $this->reflectionClass->getParentClass();
        }
        return $this->reflectionClass;
    }

    /**
     * @inheritDoc
     */
    public function getEventName()
    {
        $eventName = null;
        $reflectionClass = $this->getReflectionClass();
        if ($reflectionClass === null) {
            return null;
        }
        $eventName = $reflectionClass->getConstant('EVENT_NAME');
        return $eventName ?? $reflectionClass->getShortName();
    }

    /**
     * @inheritDoc
     */
    public function getScopeName()
    {
        $scopeName = null;
        $reflectionClass = $this->getReflectionClass();
        if ($reflectionClass === null) {
            return null;
        }
        $scopeName = $reflectionClass->getConstant('SCOPE_NAME');
        if ($scopeName === null || $scopeName === false) {
            $namespaces = explode('\\', $reflectionClass->getNamespaceName());
            $scopeName = array_pop($namespaces); // get first namespace
        }
        return $scopeName;
    }

    /**
     * @inheritDoc
     */
    public function getFullEventName()
    {
        $eventName = $this->getEventName();
        $scopeName = $this->getScopeName();

        return ($scopeName ?? '') . $eventName;
    }

    /**
     * @inheritDoc
     */
    abstract public function execute(Observer $observer);
}
