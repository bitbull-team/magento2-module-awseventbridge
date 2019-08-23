<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Model\Service;

use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config implements ConfigInterface
{
    const XML_PATH_GENERAL_STORE_URL = 'general/locale/code';
    const XML_PATH_REGION = 'aws_eventbridge/credentials/region';
    const XML_PATH_ACCESS_KEY = 'aws_eventbridge/credentials/access_key';
    const XML_PATH_SECRET_ACCESS_KEY = 'aws_eventbridge/credentials/secret_access_key';
    const XML_PATH_SOURCE = 'aws_eventbridge/credentials/source';
    const XML_PATH_DEBUG_MODE = 'aws_eventbridge/dev/debug_mode';
    const XML_PATH_DRY_RUN_MODE = 'aws_eventbridge/dev/dry_run_mode';

    const XML_PATH_EVENT_PREFIX = 'aws_eventbridge/events_';

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
            'key' => (string) $this->scopeConfig->getValue(self::XML_PATH_ACCESS_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'secret' => (string) $this->scopeConfig->getValue(self::XML_PATH_SECRET_ACCESS_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
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
    public function isDryRunModeEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DRY_RUN_MODE);
    }

    /**
     * @inheritdoc
     */
    public function isEventEnabled($eventName, $scopeName = null)
    {
        $eventName = strtolower(preg_replace('/(?<!^|\\\)[A-Z]/', '_$0', $eventName)); // convert from CamelCase to snake_case
        $scopeName = $scopeName !== null ? strtolower(preg_replace('/(?<!^|\\\)[A-Z]/', '_$0', $scopeName)) : null; // convert from CamelCase to snake_case if not null
        $configName = $scopeName === null ? $eventName : "$scopeName/$eventName"; // concatenate scope and event name
        return $this->scopeConfig->isSetFlag(self::XML_PATH_EVENT_PREFIX  . $configName);
    }
}
