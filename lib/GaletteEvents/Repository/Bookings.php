<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Bookings
 *
 * PHP version 5
 *
 * Copyright © 2018 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Repository
 * @package   GaletteEvents
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2018 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */

namespace GaletteEvents\Repository;

use Analog\Analog;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Predicate;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Db\Sql\Predicate\Operator;
use Galette\Core\Login;
use Galette\Core\Db;
use Galette\Entity\Adherent;
use Galette\Entity\Group;
use Galette\Repository\Groups;
use GaletteEvents\Booking;
use GaletteEvents\Filters\BookingsList;

/**
 * Bookings
 *
 * @category  Repository
 * @name      Bookings
 * @package   GaletteEvents
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2018 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */
class Bookings
{
    private $zdb;
    private $login;
    private $filters = false;
    private $count;
    private $sum;

    const ORDERBY_EVENT = 0;
    const ORDERBY_MEMBER = 1;
    const ORDERBY_BOOKDATE = 2;
    const ORDERBY_PAID = 3;

    const FILTER_DC_PAID = 0;
    const FILTER_PAID = 1;
    const FILTER_NOT_PAID = 2;

    /**
     * Constructor
     *
     * @param Db           $zdb     Database instance
     * @param Login        $login   Login instance
     * @param BookingsList $filters Filtering
     */
    public function __construct(Db $zdb, Login $login, $filters = null)
    {
        $this->zdb = $zdb;
        $this->login = $login;

        if ($filters === null) {
            $this->filters = new BookingsList();
        } else {
            $this->filters = $filters;
        }
    }

    /**
     * Get booking list
     *
     * @return GaletteEvents\Booking[]
     */
    public function getList()
    {
        try {
            $select = $this->buildSelect(null, true);

            if (!$this->login->isAdmin() && !$this->login->isStaff()) {
                if ($this->login->isGroupManager()) {
                    /*$groups = $this->login->managed_groups;
                    $select->where(
                        new PredicateSet(
                            array(
                                new Predicate\In(
                                    Group::PK,
                                    $this->login->managed_groups
                                ),
                                new PredicateSet(
                                    array(
                                        new Predicate\IsNull(Group::PK),
                                        new Predicate\Operator(
                                            'is_open',
                                            '=',
                                            true
                                        ),
                                        new Predicate\Operator(
                                            'begin_date',
                                            '>=',
                                            date('Y-m-d')
                                        )
                                    )
                                )
                            ),
                            PredicateSet::OP_OR
                        )
                    );*/
                } else {
                    $select->where(Adherent::PK, $this->login->id);
                    /*$select->where('is_open', true);
                    $select->where->greaterThanOrEqualTo('begin_date', date('Y-m-d'));
                    $select->where(
                        new PredicateSet(
                            array(
                                new Predicate\In(
                                    Group::PK,
                                    Groups::loadGroups($this->login->id, false, false)
                                ),
                                new Predicate\IsNull(Group::PK)
                            ),
                            PredicateSet::OP_OR
                        )
                    );*/
                }
            }

            $select->order($this->buildOrderClause());

            $this->proceedCount($select);

            $this->filters->setLimits($select);
            $results = $this->zdb->execute($select);
            $this->filters->query = $this->zdb->query_string;

            $bookings = [];
            foreach ($results as $row) {
                $booking = new Booking($this->zdb, $this->login, $row);
                $bookings[] = $booking;
            }

            return $bookings;
        } catch (\Exception $e) {
            Analog::log(
                'Cannot list bookings | ' . $e->getMessage(),
                Analog::WARNING
            );
            throw $e;
        }
    }

    /**
     * Builds the SELECT statement
     *
     * @param array $fields fields list to retrieve
     * @param bool  $count  true if we want to count members
     *                      (not applicable from static calls), defaults to false
     *
     * @return string SELECT statement
     */
    private function buildSelect($fields, $count = false)
    {
        try {
            $fieldsList = ( $fields != null )
                            ? (( !is_array($fields) || count($fields) < 1 ) ? (array)'*'
                            : implode(', ', $fields)) : (array)'*';

            $select = $this->zdb->select(EVENTS_PREFIX . Booking::TABLE, 'b');
            $select->columns($fieldsList);

            $select->join(
                array('a' => PREFIX_DB . Adherent::TABLE),
                'b.' . Adherent::PK . '= a.' . Adherent::PK
            );

            $this->buildWhereClause($select);
            $select->order(self::buildOrderClause());

            $this->calculateSum($select);

            if ($count) {
                $this->proceedCount($select);
            }

            return $select;
        } catch (\Exception $e) {
            Analog::log(
                'Cannot build SELECT clause for contributions | ' . $e->getMessage(),
                Analog::WARNING
            );
            return false;
        }
    }

    /**
     * Calculate sum of all selected contributions
     *
     * @param Select $select Original select
     *
     * @return void
     */
    private function calculateSum($select)
    {
        try {
            $sumSelect = clone $select;
            $sumSelect->reset($sumSelect::COLUMNS);
            $sumSelect->reset($sumSelect::JOINS);
            $sumSelect->reset($sumSelect::ORDER);
            $sumSelect->columns(
                array(
                    'sum' => new Expression('SUM(payment_amount)')
                )
            );

            $results = $this->zdb->execute($sumSelect);
            $result = $results->current();

            $this->sum = round($result->sum, 2);
        } catch (\Exception $e) {
            Analog::log(
                'Cannot calculate bookings sum | ' . $e->getMessage(),
                Analog::WARNING
            );
            return false;
        }
    }

    /**
     * Builds where clause, for filtering on simple list mode
     *
     * @param Select $select Original select
     *
     * @return string SQL WHERE clause
     */
    private function buildWhereClause($select)
    {
        try {
            if ($this->filters->paid_filter != null) {
                $select->where->equalTo(
                    'is_paid',
                    $this->filters->paid_filter
                );
            }

            if ($this->filters->payment_type_filter != null) {
                $select->where->equalTo(
                    'payment_method',
                    $this->filters->payment_type_filter
                );
            }

            /*if (!$this->login->isAdmin() && !$this->login->isStaff()) {
                //non staff members can only view their own contributions
                $select->where(
                    array(
                        'a.' . Adherent::PK => $this->login->id
                    )
                );
            } elseif ($this->filters->filtre_cotis_adh != null) {
                $select->where(
                    'a.' . Adherent::PK . ' = ' . $this->filters->filtre_cotis_adh
                );
            }*/
            /*if ($this->filters->filtre_transactions === true) {
                $select->where('a.trans_id IS NULL');
            }*/
        } catch (\Exception $e) {
            Analog::log(
                __METHOD__ . ' | ' . $e->getMessage(),
                Analog::WARNING
            );
        }
    }

    /**
     * Is field allowed to order? it shoulsd be present in
     * provided fields list (those that are SELECT'ed).
     *
     * @param string $field_name Field name to order by
     * @param array  $fields     SELECTE'ed fields
     *
     * @return boolean
     */
    private function canOrderBy($field_name, $fields)
    {
        if (!is_array($fields)) {
            return true;
        } elseif (in_array($field_name, $fields)) {
            return true;
        } else {
            Analog::log(
                'Trying to order by ' . $field_name  . ' while it is not in ' .
                'selected fields.',
                Analog::WARNING
            );
            return false;
        }
    }

    /**
     * Builds the order clause
     *
     * @param array $fields Fields list to ensure ORDER clause
     *                      references selected fields. Optionnal.
     *
     * @return string SQL ORDER clause
     */
    private function buildOrderClause($fields = null)
    {
        $order = array();

        switch ($this->filters->orderby) {
            case self::ORDERBY_BOOKDATE:
                if ($this->canOrderBy('booking_date', $fields)) {
                    $order[] = 'booking_date ' . $this->filters->getDirection();
                }
                break;
            case self::ORDERBY_PAID:
                if ($this->canOrderBy('id_paid', $fields)) {
                    $order[] = 'is_paid ' . $this->filters->getDirection();
                }
                break;

            /*case self::ORDERBY_NAME:
                if ($this->canOrderBy('name', $fields)) {
                    $order[] = 'name ' . $this->filters->getDirection();
                }
                break;
            case self::ORDERBY_TOWN:
                if ($this->canOrderBy('town', $fields)) {
                    $order[] = 'town ' . $this->filters->getDirection();
                }
                break;*/
        }

        return $order;
    }

    /**
     * Count events from the query
     *
     * @param Select $select Original select
     *
     * @return void
     */
    private function proceedCount($select)
    {
        try {
            $countSelect = clone $select;
            $countSelect->reset($countSelect::COLUMNS);
            $countSelect->reset($countSelect::ORDER);
            $countSelect->reset($countSelect::HAVING);
            $countSelect->columns(
                array(
                    'count' => new Expression('count(DISTINCT b.' . Booking::PK . ')')
                )
            );

            $have = $select->having;
            if ($have->count() > 0) {
                foreach ($have->getPredicates() as $h) {
                    $countSelect->where($h);
                }
            }

            $results = $this->zdb->execute($countSelect);

            $this->count = $results->current()->count;
            if (isset($this->filters) && $this->count > 0) {
                $this->filters->setCounter($this->count);
            }
        } catch (\Exception $e) {
            Analog::log(
                'Cannot count bookings | ' . $e->getMessage(),
                Analog::WARNING
            );
            return false;
        }
    }

    /**
     * Get count for current query
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Get sum
     *
     * @return double
     */
    public function getSum()
    {
        return $this->sum;
    }
}