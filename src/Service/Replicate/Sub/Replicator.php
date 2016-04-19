<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Odoo\Service\Replicate\Sub;

use Magento\Framework\ObjectManagerInterface;
use Praxigento\Core\Config as Cfg;
use Praxigento\Odoo\Data\Agg\Lot as AggLot;
use Praxigento\Odoo\Data\Agg\Warehouse as AggWarehouse;
use Praxigento\Odoo\Data\Api\Bundle\ILot as ApiLot;
use Praxigento\Odoo\Data\Api\Bundle\IWarehouse as ApiWarehouse;
use Praxigento\Odoo\Lib\Repo\ILot as IRepoModLot;
use Praxigento\Odoo\Repo\Agg\IWarehouse as IRepoModWarehouse;
use Praxigento\Odoo\Repo\IPv as IRepoModPv;
use Praxigento\Odoo\Repo\IRegistry;

class Replicator
{
    /** @var   ObjectManagerInterface */
    protected $_manObj;
    /** @var IRegistry */
    protected $_repoMod;
    /** @var  IRepoModLot */
    protected $_repoModLot;
    /** @var  IRepoModPv */
    protected $_repoModPv;
    /** @var  IRepoModWarehouse */
    protected $_repoModWrhs;
    /** @var Replicator\Product\Category */
    protected $_subProdCategory;
    /** @var Replicator\Product\Warehouse */
    protected $_subProdWarehouse;
    /** @var Replicator\Product */
    protected $_subProduct;

    public function __construct(
        ObjectManagerInterface $manObj,
        IRegistry $repoMod,
        IRepoModLot $repoModLot,
        IRepoModPv $repoModPv,
        IRepoModWarehouse $repoModWrhs,
        Replicator\Product $subProduct,
        Replicator\Product\Category $subProdCategory,
        Replicator\Product\Warehouse $subProdWarehouse
    ) {
        $this->_manObj = $manObj;
        $this->_repoMod = $repoMod;
        $this->_repoModLot = $repoModLot;
        $this->_repoModPv = $repoModPv;
        $this->_repoModWrhs = $repoModWrhs;
        $this->_subProduct = $subProduct;
        $this->_subProdCategory = $subProdCategory;
        $this->_subProdWarehouse = $subProdWarehouse;
    }

    /**
     * @param ApiLot[] $lots
     * @throws \Exception
     */
    public function processLots($lots)
    {
        /** @var  $aggData AggLot */
        $aggData = $this->_manObj->create(AggLot::class);
        foreach ($lots as $item) {
            $aggData->setOdooId($item->getId());
            $aggData->setCode($item->getCode());
            $aggData->setExpDate($item->getExpirationDate());
            $this->_repoModLot->checkExistence($aggData);
        }
    }

    /**
     * @param \Praxigento\Odoo\Data\Api\Bundle\IProduct $product
     */
    public function processProductItem($product)
    {
        assert($product instanceof \Praxigento\Odoo\Data\Api\Bundle\IProduct);
        $idOdoo = $product->getId();
        $sku = $product->getSku();
        $name = $product->getName();
        $isActive = $product->getIsActive();
        $skipProduct = false;
        $priceWholesale = $product->getPrice();
        $weight = $product->getPrice();
        $pvWholesale = $product->getPv();
        /* check does product item is already registered in Magento */
        if (!$this->_repoMod->isProductRegisteredInMage($idOdoo)) {
            if ($isActive) {
                /* create new product in Magento */
                $idMage = $this->_subProduct->create($sku, $name, $isActive, $priceWholesale, $pvWholesale, $weight);
                $this->_repoMod->registerProduct($idMage, $idOdoo);
                $this->_repoModPv->registerProductWholesalePv($idMage, $pvWholesale);
            } else {
                /* skip product replication for not active and not existing products */
                $skipProduct = true;
            }
        } else {
            /* update attributes for magento product */
            $idMage = $this->_repoMod->getProductMageIdByOdooId($idOdoo);
            $this->_subProduct->update($idMage, $name, $isActive, $priceWholesale, $weight);
            $this->_repoModPv->updateProductWholesalePv($idMage, $pvWholesale);
        }
        if (!$skipProduct) {
            /* check that categories are registered in Magento */
            $categories = $product->getCategories();
            $this->_subProdCategory->checkCategoriesExistence($categories);
            /* check product to categories links (add/remove) */
            $this->_subProdCategory->replicateCategories($idMage, $categories);
            /* update warehouse/lot/qty data  */
            $warehouses = $product->getWarehouses();
            $this->_subProdWarehouse->processWarehouses($idMage, $warehouses);
        }
    }

    /**
     * @param ApiWarehouse[] $warehouses
     * @throws \Exception
     */
    public function processWarehouses($warehouses)
    {
        foreach ($warehouses as $item) {
            $odooId = $item->getId();
            $found = $this->_repoModWrhs->getByOdooId($odooId);
            if (!$found) {
                /** @var  $aggData AggWarehouse */
                $aggData = $this->_manObj->create(AggWarehouse::class);
                $aggData->setOdooId($odooId);
                $aggData->setCurrency($item->getCurrency());
                $aggData->setWebsiteId(Cfg::DEF_WEBSITE_ID_BASE);
                $aggData->setCode($item->getCode());
                $aggData->setNote('replicated from Odoo');
                $created = $this->_repoModWrhs->create($aggData);
                if (!$created->getId()) {
                    throw new \Exception('Cannot replicate warehouse.');
                }
            }
        }
    }
}