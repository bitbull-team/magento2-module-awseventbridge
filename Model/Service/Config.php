<?php declare(strict_types=1);

namespace Bitbull\Mimmo\Model\Service;

use Bitbull\Mimmo\Api\Service\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config implements ConfigInterface
{
    const XML_PATH_GENERAL_STORE_URL = 'general/locale/code';
    const XML_PATH_REGION = 'mimmo/credentials/region';
    const XML_PATH_ACCESS_KEY = 'mimmo/credentials/access_key';
    const XML_PATH_SECRET_ACCESS_KEY = 'mimmo/credentials/secret_access_key';
    const XML_PATH_SOURCE = 'mimmo/credentials/source';
    const XML_PATH_DEBUG_MODE = 'mimmo/log/debug_mode';
    const XML_PATH_EVENT_PREFIX = 'mimmo/events';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(ScopeConfigInterface $scopeConfig, StoreManagerInterface $storeManager)
    {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function getRegion()
    {
        $region = $this->scopeConfig->getValue(self::XML_PATH_REGION, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($region === null) {
            $region = 'us-east-1';
        }
        return $region;
    }

    /**
     * @inheritdoc
     */
    public function getCredentials()
    {
        return [
            'key' => $this->scopeConfig->getValue(self::XML_PATH_ACCESS_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'secret' => $this->scopeConfig->getValue(self::XML_PATH_SECRET_ACCESS_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSource()
    {
        $customSource = $this->scopeConfig->getValue(self::XML_PATH_SOURCE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($customSource !== null) {
            return $customSource;
        }

        $storeUrl = $this->scopeConfig->getValue(self::XML_PATH_GENERAL_STORE_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($storeUrl === null) {
            return parse_url($storeUrl, PHP_URL_HOST);
        }

        return $_SERVER['HTTP_HOST'];
    }

    /**
     * @inheritdoc
     */
    public function isDebugModeEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DEBUG_MODE);
    }

    /**
     * @inheritdoc
     */
    public function isEventEnabled($eventName)
    {
        $prefix = self::XML_PATH_EVENT_PREFIX;
        $eventName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $eventName));
        return $this->scopeConfig->isSetFlag("$prefix/$eventName");
    }
}
