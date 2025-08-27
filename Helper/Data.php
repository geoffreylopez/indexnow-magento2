<?php
namespace WebAtypique\IndexNow\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ENABLED      = 'webatypique_indexnow_general/general/enabled';
    const XML_PATH_API_KEY      = 'webatypique_indexnow_general/general/api_key';
    const XML_PATH_ENDPOINT_URL = 'webatypique_indexnow_general/general/endpoint_url';

    /**
     * Vérifie si le module est activé.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Récupère l'API Key IndexNow.
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getApiKey($storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Récupère l'URL de l'endpoint IndexNow.
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getEndpointUrl($storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ENDPOINT_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
