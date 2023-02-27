<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Events
 *
 * PHP version 5
 *
 * Copyright © 2018-2023 The Galette Team
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
 * @copyright 2018-2023 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */

namespace GaletteEvents\Repository;

use Analog\Analog;
use Galette\Repository\Repository;
use GaletteEvents\Activity;
use Galette\Core\Preferences;
use Laminas\Db\Sql\Expression;
use Galette\Core\Login;
use Galette\Core\Db;
use Galette\Entity\Group;
use Galette\Repository\Groups;
use GaletteEvents\Event;
use GaletteEvents\Filters\EventsList;
use Laminas\Db\Sql\Select;
use Throwable;

/**
 * Events
 *
 * @category  Repository
 * @name      Events
 * @package   GaletteEvents
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2018-2023 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */
class Activities extends Repository
{
    private $count;

    public const ORDERBY_DATE = 0;
    public const ORDERBY_NAME = 1;

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

        $this->defaults = [
            'noon_meal' => [
                Activity::PK    => '1',
                'name'          => _T('Noon meal', 'events')
            ],
            'even_meal' => [
                Activity::PK    => '2',
                'name'          => _T('Even meal', 'events')
            ],
            'lodging'   => [
                Activity::PK    => '3',
                'name'          => _T('Lodging', 'events')
            ]
        ];

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
     * @return array
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
     *                      references selected fields. Optional.
     *
     * @return array SQL ORDER clauses
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
            $joins = $countSelect->joins;
            $countSelect->reset($countSelect::JOINS);
            foreach ($joins as $join) {
                $countSelect->join(
                    $join['name'],
                    $join['on'],
                    [],
                    $join['type']
                );
                unset($join['columns']);
            }

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
            throw $e;
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
                $delete = $this->zdb->delete(EVENTS_PREFIX . $ent::TABLE);
                $this->zdb->execute($delete);
                $this->insert($ent::TABLE, $this->defaults);

                $this->zdb->connection->commit();
                return true;
            }
        } catch (\Exception $e) {
            $this->zdb->connection->rollBack();
            throw $e;
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
                'name'          => ':name',
                'creation_date' => date('Y-m-d'),
                'is_active'     => '1',
                'comment'       => ''
            )
        );
        $stmt = $this->zdb->sql->prepareStatementForSqlObject($insert);

        foreach ($values as $name) {
            $stmt->execute([':name' => $name['name']]);
        }
    }

    /**
     * Checks for missing activities in the database
     *
     * @return bool
     */
    protected function checkUpdate()
    {
        try {
            $ent = $this->entity;
            $select = $this->zdb->select(EVENTS_PREFIX . $ent::TABLE);
            $dblist = $this->zdb->execute($select);

            $list = [];
            foreach ($dblist as $dbentry) {
                $list[] = $dbentry;
            }

            $missing = array();
            foreach ($this->defaults as $default) {
                $exists = false;
                foreach ($list as $activity) {
                    if (
                        $activity->name == $default['name']
                    ) {
                        $exists = true;
                        continue;
                    }
                }

                if ($exists === false) {
                    //text does not exists in database, insert it.
                    $missing[] = $default;
                }
            }

            if (count($missing) > 0) {
                $this->insert($ent::TABLE, $missing);

                Analog::log(
                    'Missing activities were successfully stored into database.',
                    Analog::INFO
                );
                return true;
            }
        } catch (Throwable $e) {
            Analog::log(
                'An error occurred checking missing activities.' . $e->getMessage(),
                Analog::WARNING
            );
            throw $e;
        }
        return false;
    }
}
