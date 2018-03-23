<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Odoo\Web\Customer\Pv;

use Praxigento\Core\Api\App\Web\Response\Result as WResult;
use Praxigento\Odoo\Api\Web\Customer\Pv\Add\Request as WRequest;
use Praxigento\Odoo\Api\Web\Customer\Pv\Add\Response as WResponse;
use Praxigento\Odoo\Api\Web\Customer\Pv\Add\Response\Data as WData;
use Praxigento\Odoo\Config as Cfg;
use Praxigento\Odoo\Helper\Code\Request as HCodeReq;
use Praxigento\Odoo\Repo\Data\Registry\Request as ERegRequest;

/**
 * API adapter for internal service to add PV to the Magento customer (Odoo replication).
 */
class Add
    implements \Praxigento\Odoo\Api\Web\Customer\Pv\AddInterface
{
    /** @var \Praxigento\Core\Api\App\Web\Authenticator\Rest */
    private $auth;
    /** @var \Praxigento\Odoo\Api\App\Logger\Main */
    private $logger;
    /** @var \Praxigento\Core\Api\App\Repo\Transaction\Manager */
    private $manTrans;
    /** @var \Praxigento\Downline\Repo\Dao\Customer */
    private $daoDwnlCust;
    /** @var \Praxigento\Odoo\Repo\Dao\Registry\Request */
    private $daoRegRequest;
    /** @var \Praxigento\Accounting\Repo\Dao\Type\Asset */
    private $daoTypeAsset;
    /** @var \Praxigento\Accounting\Service\Account\Asset\Transfer */
    private $servAssetTransfer;

    public function __construct(
        \Praxigento\Core\Api\App\Web\Authenticator\Rest $auth,
        \Praxigento\Odoo\Api\App\Logger\Main $logger,
        \Praxigento\Accounting\Repo\Dao\Type\Asset $daoTypeAsset,
        \Praxigento\Downline\Repo\Dao\Customer $daoDwnlCust,
        \Praxigento\Odoo\Repo\Dao\Registry\Request $daoRegRequest,
        \Praxigento\Core\Api\App\Repo\Transaction\Manager $manTrans,
        \Praxigento\Accounting\Service\Account\Asset\Transfer $servAssetTransfer
    ) {
        $this->auth = $auth;
        $this->logger = $logger;
        $this->daoTypeAsset = $daoTypeAsset;
        $this->daoDwnlCust = $daoDwnlCust;
        $this->daoRegRequest = $daoRegRequest;
        $this->manTrans = $manTrans;
        $this->servAssetTransfer = $servAssetTransfer;
    }

    public function exec($request)
    {
        assert($request instanceof \Praxigento\Odoo\Api\Web\Customer\Pv\Add\Request);
        /** define local working data */
        $data = $request->getData();
        $mlmId = $data->getCustomerMlmId();
        $notes = $data->getNotes();
        $odooRef = $data->getOdooRef();
        $pv = $data->getPv();

        $respRes = new WResult();
        $respData = new WData();

        /** perform processing */
        $respData->setOdooRef($odooRef);
        $amount = abs($pv);
        $assetId = $this->getAssetId();
        $custId = $this->getCustomerId($mlmId);
        $userId = $this->getUserId($request);
        /* prevent duplication */
        $def = $this->manTrans->begin();
        try {
            $found = $this->findDuplicates($odooRef);
            if ($found) {
                $msg = "Odoo request referenced as '$odooRef' is already processed.";
                $this->logger->error($msg);
                $respRes->setCode(WResponse::CODE_DUPLICATED);
                $respRes->setText($msg);
            } else {
                /* add PV to customer account */
                $req = new \Praxigento\Accounting\Service\Account\Asset\Transfer\Request();
                $req->setAmount($amount);
                $req->setAssetId($assetId);
                $req->setCustomerId($custId);
                $req->setUserId($userId);
                $req->setIsDirect(true);
                $req->setNote($notes);

                $resp = $this->servAssetTransfer->exec($req);
                $operId = $resp->getOperId();

                if ($operId) {
                    $this->registerOdooRequest($odooRef);
                    /* compose response */
                    $respData->setOperationId($operId);
                    $respRes->setCode(WResponse::CODE_SUCCESS);
                    $msg = "$pv PV are credited to customer #$mlmId (odoo ref. #$odooRef).";
                    $this->logger->info($msg);
                }
            }
            $this->manTrans->commit($def);
        } finally {
            /* rollback uncommitted transactions on exception */
            $this->manTrans->end($def);
        }
        /** compose result */
        $result = new WResponse();
        $result->setResult($respRes);
        $result->setData($respData);
        return $result;
    }

    /**
     * Look up for performed "Add PV to Customer" requests with the same Odoo Reference.
     *
     * @param string $odooRef
     * @return bool|\Praxigento\Odoo\Repo\Data\Registry\Request
     * @throws \Exception
     */
    private function findDuplicates($odooRef)
    {
        $entity = new ERegRequest();
        $entity->setTypeCode(HCodeReq::CUSTOMER_PV_ADD);
        $entity->setOdooRef($odooRef);
        $key = (array)$entity->get();
        $result = $this->daoRegRequest->getById($key);
        return $result;
    }

    /** @return int */
    private function getAssetId()
    {
        $result = $this->daoTypeAsset->getIdByCode(Cfg::CODE_TYPE_ASSET_PV);
        return $result;
    }

    /**
     * @param string $mlmId
     * @return int
     * @throws \Exception
     */
    private function getCustomerId($mlmId)
    {
        $customer = $this->daoDwnlCust->getByMlmId($mlmId);
        if (!$customer) {
            throw new \Exception("Cannot find customer with MLM ID: $mlmId.");
        }
        $result = $customer->getCustomerId();
        return $result;
    }

    /**
     * Get admin user to log in operations log.
     *
     * @param WRequest $request
     * @return mixed
     */
    private function getUserId($request)
    {
        $result = $this->auth->getCurrentUserId($request);
        return $result;
    }

    /**
     * Register PV add request on Magento side to prevent double processing.
     *
     * @param string $odooRef
     * @throws \Exception
     */
    private function registerOdooRequest($odooRef)
    {
        $entity = new ERegRequest();
        $entity->setTypeCode(HCodeReq::CUSTOMER_PV_ADD);
        $entity->setOdooRef($odooRef);
        $this->daoRegRequest->create($entity);
    }
}