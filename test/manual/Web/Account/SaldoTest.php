<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2018
 */

namespace Test\Praxigento\Odoo\Web\Account;

use Praxigento\Odoo\Api\Web\Account\Saldo\Request as ARequest;
use Praxigento\Odoo\Api\Web\Account\Saldo\Request\Data as ARequestData;
use Praxigento\Odoo\Api\Web\Account\Saldo\Response as AResponse;
use Praxigento\Odoo\Api\Web\Account\SaldoInterface as AService;
use Praxigento\Odoo\Config as Cfg;

include_once(__DIR__ . '/../../phpunit_bootstrap.php');

class SaldoTest
    extends \Praxigento\Core\Test\BaseCase\Manual
{
    public function test_exec()
    {
        /** @var AService $obj */
        $obj = $this->manObj->create(AService::class);
        $req = new ARequest();
        $data = new ARequestData ();
        $data->setTransTypes([Cfg::CODE_TYPE_OPER_CHANGE_BALANCE]);
        $data->setCustomers(['790001541']);
        $data->setDateFrom('2018-08-01');
        $data->setDateTo('2018-10-31');
        $req->setData($data);
        $res = $obj->exec($req);
        $this->assertInstanceOf(AResponse::class, $res);
    }


}