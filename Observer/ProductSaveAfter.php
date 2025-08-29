<?php
declare(strict_types=1);

namespace WebAtypique\IndexNow\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use WebAtypique\IndexNow\Model\Service\IndexNowSender;

class ProductSaveAfter implements ObserverInterface
{
    public function __construct(
        private UrlFinderInterface $urlFinder,
        private StoreManagerInterface $storeManager,
        private IndexNowSender $indexNowSender,
        private LoggerInterface $logger
    ) {}

    public function execute(Observer $observer): void
    {
        $product = $observer->getEvent()->getProduct();
        if (!$product instanceof ProductInterface || !$product->getId()) {
            return;
        }

        // Récupère les store views du produit ; si vide, on prend toutes les vues actives
        $storeIds = $product->getStoreIds();
        if (empty($storeIds)) {
            $stores = $this->storeManager->getStores(); // retourne les store views
            $storeIds = array_map(fn($s) => (int)$s->getId(), $stores);
        }

        foreach ($storeIds as $storeId) {
            try {
                // Cherche l'URL rewrite frontend pour cette entité/product + store view
                $rewrite = $this->urlFinder->findOneByData([
                    UrlRewrite::ENTITY_ID => $product->getId(),
                    UrlRewrite::ENTITY_TYPE => 'product',
                    UrlRewrite::STORE_ID => (int)$storeId,
                ]);

                if ($rewrite && $rewrite->getRequestPath()) {
                    $baseUrl = rtrim($this->storeManager->getStore($storeId)->getBaseUrl(), '/');
                    $requestPath = ltrim((string)$rewrite->getRequestPath(), '/');
                    $url = $baseUrl . '/' . $requestPath;

                    $this->indexNowSender->submitUrl($url);
                }
            } catch (\Throwable $e) {
                $this->logger->error(sprintf(
                    '[IndexNow] Erreur lors de la génération/envoi de l\'URL pour le produit %s sur store %d : %s',
                    (string)$product->getId(),
                    (int)$storeId,
                    $e->getMessage()
                ));
            }
        }
    }
}
