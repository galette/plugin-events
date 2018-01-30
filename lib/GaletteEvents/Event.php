<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Event entity
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
 * @category  Entity
 * @package   GaletteEvents
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2012-2014 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2012-01-17
 */

namespace GaletteEvents;

use Galette\Core\Login;
use Analog\Analog;
use Zend\Db\Sql\Expression;

/**
 * Event entity
 *
 * @category  Entity
 * @name      Event
 * @package   GaletteEvents
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2018 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */
class Event
{
    const TABLE = 'events';
    const PK = 'id_event';

    private $zdb;

    private $id;
    private $name;
    private $address;
    private $zip;
    private $town;
    private $country;
    private $begin_date;
    private $end_date;
    private $creation_date;
    private $meal = false;
    private $meal_required = false;
    private $lodging = false;
    private $lodging_required = false;
    private $open = true;
    private $id_group;

    /**
     * Default constructor
     *
     * @param Db                 $zdb  Database instance
     * @param null|int|ResultSet $args Either a ResultSet row or its id for to load
     *                                 a specific event, or null to just
     *                                 instanciate object
     */
    public function __construct(Db $zdb, $args = null)
    {
        $this->zdb = $zdb;
        if ($args == null || is_int($args)) {
            if (is_int($args) && $args > 0) {
                $this->load($args);
            } else {
                $now = date('Y-m-d');
                $this->begin_date = $now;
                $this->end_date = $now;
            }
        } elseif (is_object($args)) {
            $this->loadFromRS($args);
        }
    }

    /**
     * Loads an event from its id
     *
     * @param int $id the identifiant for the event to load
     *
     * @return bool true if query succeed, false otherwise
     */
    public function load($id)
    {
        try {
            $select = $zdb->select($this->getTableName());
            $select->where(array(self::PK => $id));

            $results = $zdb->execute($select);

            if ($results->count() > 0) {
                $this->loadFromRS($results->current());
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Analog::log(
                'Cannot load event form id `' . $id . '` | ' . $e->getMessage(),
                Analog::WARNING
            );
            return false;
        }
    }

    /**
     * Populate object from a resultset row
     *
     * @param ResultSet $r the resultset row
     *
     * @return void
     */
    private function loadFromRS($r)
    {
        $this->id = $r->id_event;
        $this->name = $r->name;
        $this->creation_date = $r->creation_date;
    }

    /**
     * Remove specified event
     *
     * @return boolean
     */
    public function remove()
    {
        $transaction = false;

        try {
            if (!$this->zdb->connection->inTransaction()) {
                $this->zdb->connection->beginTransaction();
                $transaction = true;
            }

            $delete = $this->zdb->delete($this->getTableName());
            $delete->where(
                self::PK . ' = ' . $this->id
            );
            $this->zdb->execute($delete);

            //commit all changes
            if ($transaction) {
                $this->zdb->connection->commit();
            }

            return true;
        } catch (\Exception $e) {
            if ($transaction) {
                $this->zdb->connection->rollBack();
            }
            Analog::log(
                'Unable to delete event ' . $this->name .
                ' (' . $this->id  . ') |' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Store the grouevent
     *
     * @return boolean
     */
    public function store()
    {
        global $hist;

        try {
            $values = array(
                self::PK    => $this->id,
                'name'      => $this->name
            );

            if (!isset($this->id) || $this->id == '') {
                //we're inserting a new event
                unset($values[self::PK]);
                $this->creation_date = date("Y-m-d H:i:s");
                $values['creation_date'] = $this->creation_date;

                $insert = $this->zdb->insert($this->getTableName());
                $insert->values($values);
                $add = $this->zdb->execute($insert);
                if ($add->count() > 0) {
                    if ($this->zdb->isPostgres()) {
                        $this->id = $this->zdb->driver->getLastGeneratedValue(
                            PREFIX_DB . 'groups_id_seq'
                        );
                    } else {
                        $this->id = $this->zdb->driver->getLastGeneratedValue();
                    }

                    // logging
                    $hist->add(
                        _T("Event added", "events"),
                        $this->name
                    );
                    return true;
                } else {
                    $hist->add(_T("Fail to add new event.", "events"));
                    throw new \Exception(
                        'An error occured inserting new event!'
                    );
                }
            } else {
                //we're editing an existing event
                $update = $this->zdb->update($this->getTableName());
                $update
                    ->set($values)
                    ->where(self::PK . '=' . $this->id);

                $edit = $this->zdb->execute($update);

                //edit == 0 does not mean there were an error, but that there
                //were nothing to change
                if ($edit->count() > 0) {
                    $hist->add(
                        _T("Event updated", "events"),
                        $this->name
                    );
                }
                return true;
            }
        } catch (\Exception $e) {
            Analog::log(
                'Something went wrong :\'( | ' . $e->getMessage() . "\n" .
                $e->getTraceAsString(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Get event id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get group creation date
     *
     * @param boolean $formatted Return date formatted, raw if false
     *
     * @return string
     */
    public function getCreationDate($formatted = true)
    {
        if ($formatted === true) {
            $date = new \DateTime($this->creation_date);
            return $date->format(__("Y-m-d"));
        } else {
            return $this->creation_date;
        }
    }

    /**
     * Set name
     *
     * @param string $name Event name
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get table's name
     *
     * @return string
     */
    protected function getTableName()
    {
        return EVENTS_PREFIX  . self::TABLE;
    }
}
