<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Model\Logger;

use Exception;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    /**
     * Log file name
     *
     * @var string
     */
    const LOG_FILE_NAME = 'aws-eventbridge.log';

    /**
     * Logging level
     * @var int
     */
    protected $loggerType = \Monolog\Logger::DEBUG;

    /**
     * @param DriverInterface $filesystem
     * @param string $filePath
     * @param string $fileName
     * @throws Exception
     */
    public function __construct(
        DriverInterface $filesystem,
        $filePath = null,
        $fileName = null
    ) {
        $this->fileName = '/var/log/' . self::LOG_FILE_NAME;

        parent::__construct($filesystem, $filePath, $fileName);
    }
}
