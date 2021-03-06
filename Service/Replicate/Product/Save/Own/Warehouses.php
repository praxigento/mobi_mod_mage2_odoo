<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Odoo\Service\Replicate\Product\Save\Own;

use Praxigento\Odoo\Repo\Odoo\Data\Inventory\Warehouse as DWarehouse;

/**
 * Check Odoo warehouse existence in Magento (sub-service for the parent service).
 */
class Warehouses
{
    /** @var \Praxigento\Odoo\Repo\Dao\Warehouse */
    private $daoWrhs;

    public function __construct(
        \Praxigento\Odoo\Repo\Dao\Warehouse $daoWrhs
    ) {
        $this->daoWrhs = $daoWrhs;
    }


    /**
     * @param DWarehouse[] $warehouses
     * @throws \Exception
     */
    public function execute($warehouses)
    {
        if (is_array($warehouses)) {
            foreach ($warehouses as $item) {
                $odooId = $item->getIdOdoo();
                $found = $this->daoWrhs->getByOdooId($odooId);
                if (!$found) {
                    throw new \Exception("Cannot find warehouse '$odooId'. Please create and setup this warehouse manually.");
                }
            }
        }
    }
}