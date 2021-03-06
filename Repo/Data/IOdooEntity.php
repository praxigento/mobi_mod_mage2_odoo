<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Odoo\Repo\Data;

/**
 * Interface for data object that has referenced Odoo entity.
 */
interface IOdooEntity
    extends \Praxigento\Core\Api\App\Repo\Data\Entity
{
    const A_DATE_REPLICATED = 'date_replicated';
    const A_MAGE_REF = 'mage_ref';
    const A_ODOO_REF = 'odoo_ref';

    /**
     * Get date when object have been replicated between Odoo & Mage.
     *
     * @return string
     */
    public function getDateReplicated();

    /**
     * Get Mage ID for the entity.
     *
     * @return int
     */
    public function getMageRef();

    /**
     * Get Odoo ID for the entity.
     *
     * @return int|string
     */
    public function getOdooRef();

    /**
     * Set date when object have been replicated between Odoo & Mage.
     *
     * @param string $data
     */
    public function setDateReplicated($data);

    /**
     * Set Mage ID for the entity.
     *
     * @param int $data
     */
    public function setMageRef($data);

    /**
     * Set Odoo ID for the entity.
     *
     * @param int|string $data
     */
    public function setOdooRef($data);
}