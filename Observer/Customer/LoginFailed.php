<?php
namespace Bitbull\AWSEventBridge\Observer\Customer;

use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Bitbull\AWSEventBridge\Model\Service\EventEmitter;
use Bitbull\AWSEventBridge\Observer\BaseObserver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class LoginFailed extends BaseObserver
{
    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @param LoggerInterface $logger
     * @param ConfigInterface $config
     * @param EventEmitter $eventEmitter
     * @param MessageManagerInterface $messageManager
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigInterface $config,
        EventEmitter $eventEmitter,
        MessageManagerInterface $messageManager
    ) {
        parent::__construct($logger, $config, $eventEmitter);
        $this->messageManager = $messageManager;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $postData = $observer->getRequest()->getPost();
        $errorMessages = $this->messageManager->getMessages()->getErrors();

        if (count($errorMessages) === 0) {
            $this->logger->warn('No errors found, skipping ' . $this->getEventName());
            return;
        }

        $this->eventEmitter->send($this->getFullEventName(), [
            'messages' => array_map(function($errorMessage) {
                return $errorMessage->getText();
            }, $errorMessages),
            'username' => isset($postData['login']) ? $postData['login']['username'] : null
        ]);
    }
}
