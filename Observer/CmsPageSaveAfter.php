<?php
declare(strict_types=1);

namespace WebAtypique\IndexNow\Observer;

use Magento\Cms\Model\Page;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use WebAtypique\IndexNow\Model\Service\IndexNowSender;

class CmsPageSaveAfter implements ObserverInterface
{
    protected $_page;

    public function __construct(
        private StoreManagerInterface $storeManager,
        private ScopeConfigInterface $scopeConfig,
        private IndexNowSender $indexNowSender,
        private LoggerInterface $logger
    ) {}

    public function execute(Observer $observer): void
    {
        $this->_page = $observer->getEvent()->getObject();
        if (!$this->_page instanceof Page || !$this->_page->getPageId()) {
            return;
        }

        $identifier = $this->_page->getIdentifier();
        if (!$identifier) {
            return;
        }

        // Récupérer les store views de la page
        $storeIds = $this->_page->getStoreId();
        if ($storeIds === null || in_array(0, (array)$storeIds, true)) {
            // "All Store Views" → toutes les stores actives
            $stores = $this->storeManager->getStores();
            $storeIds = array_map(fn($s) => (int)$s->getId(), $stores);
        }

        foreach ($storeIds as $storeId) {
            try {
                $baseUrl = rtrim($this->storeManager->getStore($storeId)->getBaseUrl(), '/');

                // Suffixe éventuel défini dans la config
                $suffix = (string) $this->scopeConfig->getValue(
                    'cms/page/url_suffix',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                );

                // Attention : si la page est la home, pas d'URL propre
                if ($identifier === 'home') {
                    $url = $baseUrl . '/';
                } else {
                    $url = $baseUrl . '/' . ltrim($identifier, '/') . $suffix;
                }

                $this->indexNowSender->submitUrl($url);
            } catch (\Throwable $e) {
                $this->logger->error(sprintf(
                    '[IndexNow] Erreur lors de l\'envoi de la page CMS %s sur store %d : %s',
                    (string)$this->_page->getId(),
                    (int)$storeId,
                    $e->getMessage()
                ));
            }
        }
    }
}
