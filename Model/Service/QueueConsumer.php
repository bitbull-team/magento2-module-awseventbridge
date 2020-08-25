<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Model\Service;

use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Bitbull\AWSEventBridge\Model\Service\EventEmitter;
use Exception;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\EntityManager\EntityManager;
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
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Event emitter.
     *
     * @param LoggerInterface $logger
     * @param EventEmitter $eventEmitter
     * @param SerializerJson $serializerJson
     * @param EntityManager $entityManager
     */
    public function __construct(
        LoggerInterface $logger,
        EventEmitter $eventEmitter,
        SerializerJson $serializerJson,
        EntityManager $entityManager
    ) {
        $this->logger = $logger;
        $this->eventEmitter = $eventEmitter;
        $this->serializerJson = $serializerJson;
        $this->entityManager = $entityManager;
    }

    /**
     * Process
     *
     * @param \Magento\AsynchronousOperations\Api\Data\OperationInterface $operation
     * @throws Exception
     *
     * @return void
     */
    public function process(\Magento\AsynchronousOperations\Api\Data\OperationInterface $operation)
    {
        try {
            $serializedData = $operation->getSerializedData();
            $payload = $this->serializerJson->unserialize($serializedData);

            if (!isset($payload['name'], $payload['data'])) {
                throw new \InvalidArgumentException("Invalid queue message: 'name' and 'data' are required properties.");
            }
            $this->eventEmitter->sendImmediately($payload['name'], $payload['data']);

        } catch (\Zend_Db_Adapter_Exception $e) {
            $this->logger->logException($e);
            if ($e instanceof \Magento\Framework\DB\Adapter\LockWaitException
                || $e instanceof \Magento\Framework\DB\Adapter\DeadlockException
                || $e instanceof \Magento\Framework\DB\Adapter\ConnectionException
            ) {
                $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = $e->getMessage();
            } else {
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = $e->getMessage();
            }
        } catch (Exception $e) {
            $this->logger->logException($e);
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        }

        $operation->setStatus($status ?? OperationInterface::STATUS_TYPE_COMPLETE)
            ->setErrorCode($errorCode ?? null)
            ->setResultMessage($message ?? null);

        $this->entityManager->save($operation);
    }
}
