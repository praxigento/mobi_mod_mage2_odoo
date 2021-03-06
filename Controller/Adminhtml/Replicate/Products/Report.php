<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Odoo\Controller\Adminhtml\Replicate\Products;

use Praxigento\Odoo\Config as Cfg;

class Report
    extends \Praxigento\Core\App\Action\Back\Base
{

    public function __construct(
        \Magento\Backend\App\Action\Context $context
    ) {
        $aclResource = Cfg::MODULE . '::' . Cfg::ACL_REPLICATE;
        $activeMenu = Cfg::MODULE . '::' . Cfg::MENU_REPLICATE_PRODUCTS;
        $breadcrumbLabel = 'Replicate Products';
        $breadcrumbTitle = 'Replicate Products';
        $pageTitle = 'Replicate Products';
        parent::__construct(
            $context,
            $aclResource,
            $activeMenu,
            $breadcrumbLabel,
            $breadcrumbTitle,
            $pageTitle
        );
    }
}