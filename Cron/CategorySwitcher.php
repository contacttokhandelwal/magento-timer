<?php

namespace BlueAcorn\ProductCountdown\Cron;

class CategorySwitcher {

    public function __construct(
    \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement
    , \Magento\Catalog\Api\CategoryLinkRepositoryInterface $categoryLinkRepo
    , \Magento\Catalog\Model\Product $productModel
    , \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    , \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->categoryLinkRepo = $categoryLinkRepo;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->productModel = $productModel;
        $this->date = $date;
        $this->_scopeConfig = $scopeConfig;
    }

    public function execute() {

        $categoryId = $this->getConfig('blueAcorn_productCountdown/timer/cat_id');

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/cron.log');
        $logger = new \Zend\Log\Logger();

        if ($categoryId) {
            $pc = $this->productModel->getCollection()
                    ->addAttributeToFilter('countdown_timer', array('lteq' => $this->date->gmtDate()));

            if ($pc->getData()) {
                foreach ($pc as $p) {
                    $this->removeCurrentCategories($p->getId());
                    $this->assignCategory($p->getSku(), $categoryId);
                    $currentProduct = $this->productModel->load($p->getId());
                    $currentProduct->setCountdownTimer('');
                    $currentProduct->save();
                }
                $logger->addWriter($writer);
                $logger->info('Products Moved');
            }
        } else {
            $logger->addWriter($writer);
            $logger->info('No category Id passed');
        }
    }

    public function removeCurrentCategories($pid) {
        $_product = $this->productModel->load($pid);
        $productSku = $_product->getSku();

        // all catgeories of a category
        $currentCategories = $_product->getCategoryIds();
        foreach ($currentCategories as $categoryId) {
            $this->categoryLinkRepo->deleteByIds($categoryId, $productSku);
        }
        // reindex a product in magento                 

        $productCategoryIndexer = $this->_objectManager
                ->get('Magento\Framework\Indexer\IndexerRegistry')
                ->get(\Magento\Catalog\Model\Indexer\Product\Category::INDEXER_ID);
        $productCategoryIndexer->reindexRow($_product->getId());
    }

    public function assignCategory($sku, $new_category_id) {
        $new_category_id = array($new_category_id);
        $this->categoryLinkManagement->assignProductToCategories($sku, $new_category_id);
    }

    public function getConfig($config_path) {
        return $this->_scopeConfig->getValue(
                        $config_path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

}
