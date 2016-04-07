<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Odoo\Lib\Service\Replicate;

use Magento\Framework\ObjectManagerInterface;
use Praxigento\Core\Repo\ITransactionManager;
use Praxigento\Odoo\Api\Data\IBundle;
use Praxigento\Odoo\Repo\Agg\IWarehouse as RepoWarehouse;
use Praxigento\Odoo\Lib\Service\IReplicate;

class Call implements IReplicate
{
    /** @var   ObjectManagerInterface */
    private $_manObj;
    /** @var  ITransactionManager */
    private $_manTrans;
    /** @var  RepoWarehouse */
    private $_repoWrhs;
    /** @var  Sub\Replicator */
    private $_subReplicator;

    /**
     * Call constructor.
     */
    public function __construct(
        ObjectManagerInterface $manObj,
        ITransactionManager $manTrans,
        RepoWarehouse $repoWrhs,
        Sub\Replicator $subReplicator
    ) {
        $this->_manObj = $manObj;
        $this->_manTrans = $manTrans;
        $this->_repoWrhs = $repoWrhs;
        $this->_subReplicator = $subReplicator;
    }

    /**
     * @param Request\ProductSave $req
     * @return  Response\ProductSave
     */
    public function productSave(Request\ProductSave $req)
    {
        /** @var  $bundle IBundle */
        $bundle = $req->getProductBundle();
        $options = $bundle->getOption();
        $warehouses = $bundle->getWarehouses();
        $lots = $bundle->getLots();
        $products = $bundle->getProducts();
        /* replicate all data in one transaction */
        $trans = $this->_manTrans->transactionBegin();
        try {
            /* replicate warehouses & lots */
            $this->_subReplicator->processWarehouses($warehouses);
            $this->_subReplicator->processLots($lots);
            /* replicate products */
            foreach ($products as $odooId => $prod) {
                $sku = $prod->getSku();
                $price = $prod->getPrice();
                $pv = $prod->getPv();
            }
            $this->_manTrans->transactionCommit($trans);
        } finally {
            // transaction will be rolled back if commit is not done (otherwise - do nothing)
            $this->_manTrans->transactionClose($trans);
        }
        return;
    }
}