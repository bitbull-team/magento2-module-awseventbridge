<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Model\Service;

use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Magento\Framework\Logger\Monolog;

class Logger implements LoggerInterface
{
    /**
     * @var Monolog|null
     */
    protected $logger = null;

    /**
     * @var boolean
     */
    protected $debugMode;

    /**
     * @param Monolog $logger
     * @param ConfigInterface $config
     */
    public function __construct(Monolog $logger, ConfigInterface $config)
    {
        $this->logger = $logger;
        $this->debugMode = $config->isDebugModeEnabled();
    }

    /**
     * @inheritdoc
     */
    public function log($message, $level = null, $context = [])
    {
        if ($level === null) {
            $level = Monolog::INFO;
        }
        $this->logger->log($level, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function logException($exception)
    {
        $this->log((string)$exception, Monolog::CRITICAL);
    }

    /**
     * @inheritdoc
     */
    public function debug($message, $context = [])
    {
        if ($this->debugMode) {
            $this->log($message, Monolog::DEBUG, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function error($message, $context = [])
    {
        $this->log($message, Monolog::ERROR, $context);
    }

    /**
     * @inheritdoc
     */
    public function warn($message, $context = [])
    {
        $this->log($message, Monolog::WARNING, $context);
    }

    /**
     * @inheritdoc
     */
    public function info($message, $context = [])
    {
        $this->log($message, Monolog::INFO, $context);
    }
}
