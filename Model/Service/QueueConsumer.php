<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Model\Service;

use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Exception;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;

class QueueConsumer
{
    /**
     * @var EventEmitter
     */
    private $eventEmitter;

    /**
     * @var SerializerJson
     */
    protected $serializerJson;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Event emitter.
     *
     * @param LoggerInterface $logger
     * @param EventEmitter $eventEmitter
     * @param SerializerJson $serializerJson
     */
    public function __construct(
        LoggerInterface $logger,
        EventEmitter $eventEmitter,
        SerializerJson $serializerJson
    ) {
        $this->logger = $logger;
        $this->eventEmitter = $eventEmitter;
        $this->serializerJson = $serializerJson;
    }

    /**
     * Process
     *
     * @param string[] $messages
     * @return void
     */
    public function process($messages)
    {
        try {
            foreach ($messages as $message) {
                $payload = $this->serializerJson->unserialize($message);

                if (!isset($payload['name'], $payload['data'])) {
                    $this->logger->error("Invalid queue message: 'name' and 'data' are required properties.");
                    continue;
                }
                $this->eventEmitter->sendImmediately($payload['name'], $payload['data']);
            }
        } catch (Exception $e) {
            $this->logger->logException($e);
        }

    }
}
