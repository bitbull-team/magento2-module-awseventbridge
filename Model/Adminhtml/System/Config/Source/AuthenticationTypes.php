<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Model\Adminhtml\System\Config\Source;

class AuthenticationTypes implements \Magento\Framework\Option\ArrayInterface
{
    const TYPE_KEYS = 'keys';
    const TYPE_ENVIRONMENT = 'env';
    const TYPE_EC2 = 'ec2';

    /**
     * Return authentication types for AWS services
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'keys', 'label' => 'Specify access and secret keys'],
            ['value' => 'env', 'label' => 'Use environment variables'],
            ['value' => 'ec2', 'label' => 'Use EC2 Instance Role'],
        ];
    }
}
