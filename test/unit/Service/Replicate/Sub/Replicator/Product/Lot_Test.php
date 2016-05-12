<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\Odoo\Service\Replicate\Sub\Replicator\Product;

use Praxigento\Warehouse\Data\Entity\Quantity;

include_once(__DIR__ . '/../../../../../phpunit_bootstrap.php');

class Lot_UnitTest extends \Praxigento\Core\Test\BaseMockeryCase
{


    /** @var  \Mockery\MockInterface */
    private $mRepoRegistry;
    /** @var  \Mockery\MockInterface */
    private $mRepoWrhsEntityQty;
    /** @var  Lot */
    private $obj;

    protected function setUp()
    {
        parent::setUp();
        /** create mocks */
        $this->mRepoRegistry = $this->_mock(\Praxigento\Odoo\Repo\IRegistry::class);
        $this->mRepoWrhsEntityQty = $this->_mock(\Praxigento\Warehouse\Repo\Entity\IQuantity::class);
        /** create object to test */
        $this->obj = new Lot(
            $this->mRepoRegistry,
            $this->mRepoWrhsEntityQty
        );
    }

    public function test_cleanupLots()
    {
        /** === Test Data === */
        $STOCK_ITEM_ID = 32;
        $LOT_ID_O1 = 41;
        $LOT_ID_M1 = 21;
        $LOT_ID_M2 = 22;
        $LOTS = [];
        $LOTS_EXIST = [
            [Quantity::ATTR_LOT_REF => $LOT_ID_M1],
            [Quantity::ATTR_LOT_REF => $LOT_ID_M2]
        ];
        /** === Setup Mocks === */
        // $ref = $this->_repoWarehouseEntityQuantity->getRef();
        $mRef = new \Praxigento\Warehouse\Data\Entity\Quantity();
        $this->mRepoWrhsEntityQty
            ->shouldReceive('getRef')->once()
            ->andReturn($mRef);
        // $lotsExist = $this->_repoWarehouseEntityQuantity->get($where);
        $this->mRepoWrhsEntityQty
            ->shouldReceive('get')->once()
            ->andReturn($LOTS_EXIST);
        // $lotIdOdoo = $lot->getId();
        $mLot = $this->_mock(\Praxigento\Odoo\Data\Api\Bundle\Product\Warehouse\ILot::class);
        $LOTS[] = $mLot;
        $mLot->shouldReceive('getId')->once()
            ->andReturn($LOT_ID_O1);
        // $lotIdMage = $this->_repoRegistry->getLotMageIdByOdooId($lotIdOdoo);
        $this->mRepoRegistry
            ->shouldReceive('getLotMageIdByOdooId')->once()
            ->andReturn($LOT_ID_M1);
        // $this->_repoWarehouseEntityQuantity->deleteById($pk);
        $this->mRepoWrhsEntityQty
            ->shouldReceive('deleteById')->once();
        /** === Call and asserts  === */
        $this->obj->cleanupLots($STOCK_ITEM_ID, $LOTS);
    }

    public function test_constructor()
    {
        /** === Call and asserts  === */
        $this->assertInstanceOf(Lot::class, $this->obj);
    }

    public function test_processLot_create()
    {
        /** === Test Data === */
        $STOCK_ITEM_ID = 32;
        $LOT_ID_O1 = 41;
        $LOT_QTY = 432;
        $LOT_ID_M1 = 21;
        $LOT = new \Praxigento\Odoo\Data\Api\Bundle\Product\Warehouse\Def\Lot();
        $QTY_ITEM = null;
        /** === Setup Mocks === */
        // $ref = $this->_repoWarehouseEntityQuantity->getRef();
        $mRef = new \Praxigento\Warehouse\Data\Entity\Quantity();
        $this->mRepoWrhsEntityQty
            ->shouldReceive('getRef')->once()
            ->andReturn($mRef);
        // $lotIdOdoo = $lot->getId();
        $LOT->setId($LOT_ID_O1);
        // $qty = $lot->getQuantity();
        $LOT->setQuantity($LOT_QTY);
        // $lotIdMage = $this->_repoRegistry->getLotMageIdByOdooId($lotIdOdoo);
        $this->mRepoRegistry
            ->shouldReceive('getLotMageIdByOdooId')->once()
            ->andReturn($LOT_ID_M1);
        // $qtyItem = $this->_repoWarehouseEntityQuantity->getById($pk);
        $this->mRepoWrhsEntityQty
            ->shouldReceive('getById')->once()
            ->andReturn($QTY_ITEM);
        // $this->_repoWarehouseEntityQuantity->create($pk);
        $this->mRepoWrhsEntityQty
            ->shouldReceive('create')->once()
            ->andReturn();
        /** === Call and asserts  === */
        $this->obj->processLot($STOCK_ITEM_ID, $LOT);
    }

    public function test_processLot_update()
    {
        /** === Test Data === */
        $STOCK_ITEM_ID = 32;
        $LOT_ID_O1 = 41;
        $LOT_QTY = 432;
        $LOT_ID_M1 = 21;
        $LOT = new \Praxigento\Odoo\Data\Api\Bundle\Product\Warehouse\Def\Lot();
        $QTY_ITEM = 'some item';
        /** === Setup Mocks === */
        // $ref = $this->_repoWarehouseEntityQuantity->getRef();
        $mRef = new \Praxigento\Warehouse\Data\Entity\Quantity();
        $this->mRepoWrhsEntityQty
            ->shouldReceive('getRef')->once()
            ->andReturn($mRef);
        // $lotIdOdoo = $lot->getId();
        $LOT->setId($LOT_ID_O1);
        // $qty = $lot->getQuantity();
        $LOT->setQuantity($LOT_QTY);
        // $lotIdMage = $this->_repoRegistry->getLotMageIdByOdooId($lotIdOdoo);
        $this->mRepoRegistry
            ->shouldReceive('getLotMageIdByOdooId')->once()
            ->andReturn($LOT_ID_M1);
        // $qtyItem = $this->_repoWarehouseEntityQuantity->getById($pk);
        $this->mRepoWrhsEntityQty
            ->shouldReceive('getById')->once()
            ->andReturn($QTY_ITEM);
        // $this->_repoWarehouseEntityQuantity->updateById($bind, $pk);
        $this->mRepoWrhsEntityQty
            ->shouldReceive('updateById')->once()
            ->andReturn();
        /** === Call and asserts  === */
        $this->obj->processLot($STOCK_ITEM_ID, $LOT);
    }

}