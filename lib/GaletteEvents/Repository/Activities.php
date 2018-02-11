<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Events
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
use Galette\Repository\Repository;
use GaletteEvents\Activity;
use Galette\Core\Preferences;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Predicate;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Db\Sql\Predicate\Operator;
use Galette\Core\Login;
use Galette\Core\Db;
use Galette\Entity\Group;
use Galette\Repository\Groups;
use GaletteEvents\Event;
use GaletteEvents\Filters\EventsList;

/**
 * Events
 *
 * @category  Repository
 * @name      Events
 * @package   GaletteEvents
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2018 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */
class Activities extends Repository
{
    private $count;

    const ORDERBY_DATE = 0;
    const ORDERBY_NAME = 1;

    /**
     * Constructor
     *
     * @param Db          $zdb         Database instance
     * @param Login       $login       Login instance
     * @param Preferences $preferences Preferences instance
     * @param EventsList  $filters     Filtering
     */
    public function __construct(Db $zdb, Login $login, Preferences $preferences, $filters = null)
    {
        $this->zdb = $zdb;
        $this->login = $login;
        parent::__construct($zdb, $preferences, $login, 'Activity', 'GaletteEvents', EVENTS_PREFIX);

        if ($filters === null) {
            $this->filters = new EventsList();
        } else {
            $this->filters = $filters;
        }
    }

    /**
     * Get activities list
     *
     * @return GaletteEvents\Event[]
     */
    public function getList()
    {
        try {
            $select = $this->zdb->select(EVENTS_PREFIX . Activity::TABLE, 'ac');
            $select->order($this->buildOrderClause());

            $this->proceedCount($select);

            $this->filters->setLimits($select);
            $results = $this->zdb->execute($select);
            $this->filters->query = $this->zdb->query_string;

            $activities = [];
            foreach ($results as $row) {
                $activity = new Activity($this->zdb, $this->login, $row);
                $activities[] = $activity;
            }

            return $activities;
        } catch (\Exception $e) {
            Analog::log(
                'Cannot list activities | ' . $e->getMessage(),
                Analog::WARNING
            );
            throw $e;
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
            case self::ORDERBY_DATE:
                if ($this->canOrderBy('creation_date', $fields)) {
                    $order[] = 'creation_date ' . $this->filters->getDirection();
                }
                break;
            case self::ORDERBY_NAME:
                if ($this->canOrderBy('name', $fields)) {
                    $order[] = 'name ' . $this->filters->getDirection();
                }
                break;
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
                    'count' => new Expression('count(DISTINCT ac.' . Activity::PK . ')')
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
                'Cannot count activities | ' . $e->getMessage(),
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
     * Add default activities in database
     *
     * @param boolean $check_first Check first if it seem initialized
     *
     * @return boolean
     */
    public function installInit($check_first = true)
    {
        try {
            $ent = $this->entity;
            //first of all, let's check if data seem to have already
            //been initialized
            $proceed = false;
            if ($check_first === true) {
                $select = $this->zdb->select(EVENTS_PREFIX . $ent::TABLE);
                $select->columns(
                    array(
                        'counter' => new Expression('COUNT(' . $ent::PK . ')')
                    )
                );

                $results = $this->zdb->execute($select);
                $result = $results->current();
                $count = $result->counter;
                if ($count == 0) {
                    //if we got no values in texts table, let's proceed
                    $proceed = true;
                } else {
                    if ($count < count($this->defaults)) {
                        return $this->checkUpdate();
                    }
                    return false;
                }
            } else {
                $proceed = true;
            }

            if ($proceed === true) {
                $this->zdb->connection->beginTransaction();

                //first, we drop all values
                $delete = $this->zdb->delete($ent::TABLE);
                $this->zdb->execute($delete);
                $this->insert($ent::TABLE, $this->defaults);

                $this->zdb->connection->commit();
                return true;
            }
        } catch (\Exception $e) {
            $this->zdb->connection->rollBack();
            return $e;
        }
    }

    /**
     * Insert values in database
     *
     * @param string $table  Table name
     * @param array  $values Values to insert
     *
     * @return void
     */
    protected function insert($table, $values)
    {
        $insert = $this->zdb->insert(EVENTS_PREFIX . $table);
        $insert->values(
            array(
                Activity::PK    => ':' . Activity::PK,
                'name'          => ':name',
                'creation_date' => ':creation_date',
                'is_active'     => ':is_active',
                'comment'       => ':comment'
            )
        );
        $stmt = $this->zdb->sql->prepareStatementForSqlObject($insert);

        foreach ($values as $value) {
            $stmt->execute($value);
        }
    }
}
