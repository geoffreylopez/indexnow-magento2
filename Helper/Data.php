<?php
namespace WebAtypique\IndexNow\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ENABLED      = 'webatypique_indexnow_general/general/enabled';
    const XML_PATH_API_KEY      = 'webatypique_indexnow_general/general/api_key';
    const XML_PATH_ENDPOINT_URL = 'webatypique_indexnow_general/general/endpoint_url';
    const XML_PATH_KEY_LOCATION = 'webatypique_indexnow_general/general/key_location';


    /**
     * Vérifie si le module est activé
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Récupère l'API Key IndexNow
     */
    public function getApiKey(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Récupère l'emplacement de la clé (key location)
     */
    public function getKeyLocation(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_KEY_LOCATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Récupère l'URL de l'endpoint IndexNow.
     */
    public function getEndpointUrl(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ENDPOINT_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
