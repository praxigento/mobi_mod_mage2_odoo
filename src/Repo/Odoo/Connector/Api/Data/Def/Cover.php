<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Odoo\Repo\Odoo\Connector\Api\Data\Def;

use Flancer32\Lib\DataObject;
use Praxigento\Odoo\Repo\Odoo\Connector\Api\Data\ICover;

class Cover extends DataObject implements ICover
{
    const PATH_RESULT_DATA = '/result';

    public function getResultData()
    {
        $result = parent::get(self::PATH_RESULT_DATA);
        return $result;
    }

}