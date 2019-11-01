<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Block\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use \Magento\Config\Block\System\Config\Form\Field;
use \Magento\Framework\Data\Form\Element\AbstractElement;
use \Magento\Framework\HTTP\ZendClientFactory as HttpZendClientFactory;

class CheckEC2Instance extends Field
{
    /**
     * @var HttpZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @param Context $context
     * @param HttpZendClientFactory $httpClientFactory
     * @param array $data
     */
    public function __construct(Context $context, HttpZendClientFactory $httpClientFactory, array $data = [])
    {
        parent::__construct($context, $data);
        $this->httpClientFactory = $httpClientFactory;
    }

    /**
     * @inheritdoc
     */
    public function render(AbstractElement $element)
    {
        $ec2InstanceMetadata = $this->getInstanceMetadata();
        ob_start();
        ?>
        <td class="label"></td>
        <?php if ($ec2InstanceMetadata === false): ?>
            <td style="color: #e22626;" class="value">WARNING: it seems Magento is not running on an EC2 instance.</td>
        <?php else: ?>
            <td style="color: #185b00;" class="value">OK: Magento is running on an EC2 instance.</td>
        <?php endif ?>
        <?php
        $html = ob_get_clean();
        return $this->_decorateRowHtml($element, $html);
    }

    /**
     * Get current instance metadata
     *
     * @return array|boolean
     */
    private function getInstanceMetadata() {
        $response = null;
        $httpClient = $this->httpClientFactory->create();
        try {
            // Doc: https://docs.aws.amazon.com/en_us/AWSEC2/latest/UserGuide/ec2-instance-metadata.html#instancedata-data-retrieval
            $httpClient->setUri('http://169.254.169.254/latest/meta-data/');
            $httpClient->setMethod(\Zend_Http_Client::GET);
            $httpClient->setHeaders('Accept','application/json');
            $httpClient->setConfig([
                'timeout' => 2 // Response should be quick
            ]);
            $response = $httpClient->request();
        } catch (\Zend_Http_Client_Exception $e) {
            return false;
        }

        return $response !== null ? $response->getBody() : false;
    }
}
