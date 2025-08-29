<?php
declare(strict_types=1);

namespace WebAtypique\IndexNow\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use WebAtypique\IndexNow\Model\Service\IndexNowSender;

class CategorySaveAfter implements ObserverInterface
{
    public function __construct(
        private UrlFinderInterface $urlFinder,
        private StoreManagerInterface $storeManager,
        private IndexNowSender $indexNowSender,
        private LoggerInterface $logger
    ) {}

    public function execute(Observer $observer): void
    {
        $category = $observer->getEvent()->getCategory();
        if (!$category instanceof CategoryInterface || !$category->getId()) {
            return;
        }

        // Récupère les store views liés à la catégorie ; sinon toutes
        $storeIds = $category->getStoreIds();
        if (empty($storeIds)) {
            $stores = $this->storeManager->getStores();
            $storeIds = array_map(fn($s) => (int)$s->getId(), $stores);
        }

        foreach ($storeIds as $storeId) {
            try {
                $rewrite = $this->urlFinder->findOneByData([
                    UrlRewrite::ENTITY_ID => $category->getId(),
                    UrlRewrite::ENTITY_TYPE => 'category',
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
                    '[IndexNow] Erreur lors de la génération/envoi de l\'URL pour la catégorie %s sur store %d : %s',
                    (string)$category->getId(),
                    (int)$storeId,
                    $e->getMessage()
                ));
            }
        }
    }
}
