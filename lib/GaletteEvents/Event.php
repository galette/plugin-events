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

use Galette\Core\Db;
use Galette\Core\Login;
use Galette\Entity\Group;
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
    private $login;
    private $errors;

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
    private $group;

    /**
     * Default constructor
     *
     * @param Db                 $zdb   Database instance
     * @param Login              $login Login instance
     * @param null|int|ResultSet $args  Either a ResultSet row or its id for to load
     *                                  a specific event, or null to just
     *                                  instanciate object
     */
    public function __construct(Db $zdb, Login $login, $args = null)
    {
        $this->zdb = $zdb;
        $this->login = $login;
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
            $select = $this->zdb->select($this->getTableName());
            $select->where(array(self::PK => $id));

            if (!$this->login->isAdmin() && !$this->login->isStaff()) {
                $select->where->in(Group::PK, $this->login->managed_groups);
            }

            $results = $this->zdb->execute($select);

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
            throw $e;
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
        $this->address = $r->address;
        $this->zip = $r->zip;
        $this->town = $r->town;
        $this->country = $r->country;
        $this->begin_date = $r->begin_date;
        $this->end_date = $r->end_date;
        $this->creation_date = $r->creation_date;
        $this->meal = $r->has_meal;
        $this->meal_required = $r->is_meal_required;
        $this->lodging = $r->has_lodging;
        $this->lodging_required = $r->is_lodging_required;
        $this->open = $r->is_open;
        $this->group = $r->id_group;
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
     * Check posted values validity
     *
     * @param array $values All values to check, basically the $_POST array
     *                      after sending the form
     *
     * @return true|array
     */
    public function check($values)
    {
        $this->errors = array();

        if (!isset($values['begin_date']) || empty($values['begin_date'])) {
            $this->errors[] = _T('Begin date is mandatory', 'events');
        } else {
            //handle dates
            foreach (['begin_date', 'end_date'] as $datefield) {
                if (isset($values[$datefield])) {
                    $value = $values[$datefield];
                    try {
                        $d = \DateTime::createFromFormat(__("Y-m-d"), $value);
                        if ($d === false) {
                            //try with non localized date
                            $d = \DateTime::createFromFormat("Y-m-d", $value);
                            if ($d === false) {
                                throw new \Exception('Incorrect format');
                            }
                        }
                        $this->$datefield = $d->format('Y-m-d');
                    } catch (\Exception $e) {
                        Analog::log(
                            'Wrong date format. field: ' . $datefield .
                            ', value: ' . $value . ', expected fmt: ' .
                            __("Y-m-d") . ' | ' . $e->getMessage(),
                            Analog::INFO
                        );
                        if ($datefield == 'begin_date') {
                            $label = _T('Begin date', 'events');
                        } else {
                            $label = _T('End date', 'events');
                        }
                        $this->errors[] = str_replace(
                            array(
                                '%date_format',
                                '%field'
                            ),
                            array(
                                __("Y-m-d"),
                                $label
                            ),
                            _T("- Wrong date format (%date_format) for %field!")
                        );
                    }
                }
            }

            if (!isset($values['end_date'])) {
                $this->end_date = $this->begin_date;
            } elseif (!count($this->errors)) {
                $dend = new \DateTime($this->end_date);
                $dbegin = new \DateTime($this->begin_date);
                if ($dend < $dbegin) {
                    $this->errors[] = _T('End date must be later or equal to begin date', 'events');
                }
            }
        }

        if (!isset($values['name']) || empty($values['name'])) {
            $this->errors[] = _T('Name is mandatory', 'events');
        } else {
            $this->name = $values['name'];
        }

        if ($this->login->isAdmin() || $this->login->isStaff()) {
            if (isset($values['group']) && !empty($values['group'])) {
                $this->group = $values['group'];
            }
        } else {
            if (!isset($values['group'])
                || empty($values['group'])
                || !in_array($values['group'], $this->login->managed_groups)
            ) {
                $this->errors[] = _T('Please select a group you own!', 'events');
            } else {
                $this->group = $values['group'];
            }
        }

        if (!isset($values['town']) || empty($values['town'])) {
            $this->errors[] = _T('Town is mandatory', 'events');
        } else {
            $this->town = $values['town'];
        }

        $otherfields = [
            'address',
            'zip',
            'country',
        ];
        foreach ($otherfields as $otherfield) {
            if (isset($values[$otherfield])) {
                $this->$otherfield = $values[$otherfield];
            }
        }

        if (isset($values['meal_required']) && !isset($values['meal'])) {
            $this->errors[] = _T('Cannot set meal as mandatory if there is no meal :)', 'events');
        } else {
            if (isset($values['meal'])) {
                $this->meal = true;
            }
            if (isset($values['meal_required'])) {
                $this->meal_required = true;
            }
        }

        if (isset($values['lodging_required']) && !isset($values['lodging'])) {
            $this->errors[] = _T('Cannot set lodging as mandatory if there is no lodging :)', 'events');
        } else {
            if (isset($values['lodging'])) {
                $this->lodging = true;
            }
            if (isset($values['lodging_required'])) {
                $this->lodging_required = true;
            }
        }

        if (count($this->errors) > 0) {
            Analog::log(
                'Some errors has been throwed attempting to edit/store an event' . "\n" .
                print_r($this->errors, true),
                Analog::ERROR
            );
            return $this->errors;
        } else {
            Analog::log(
                'Event checked successfully.',
                Analog::DEBUG
            );
            return true;
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
                self::PK                => $this->id,
                'name'                  => $this->name,
                'address'               => $this->address,
                'zip'                   => $this->zip,
                'town'                  => $this->town,
                'country'               => ($this->country ? $this->country : new Expression('NULL')),
                'begin_date'            => $this->begin_date,
                'end_date'              => $this->end_date,
                'has_meal'              => ($this->meal ? $this->meal :
                                                ($this->zdb->isPostgres() ? 'false' : 0)),
                'is_meal_required'      => ($this->meal_required ? $this->meal_required :
                                                ($this->zdb->isPostgres() ? 'false' : 0)),
                'has_lodging'           => ($this->lodging ? $this->lodging :
                                                ($this->zdb->isPostgres() ? 'false' : 0)),
                'is_lodging_required'   => ($this->lodging_required ? $this->lodging_required :
                                                ($this->zdb->isPostgres() ? 'false' : 0)),
                'is_open'               => ($this->open ? $this->open :
                                                ($this->zdb->isPostgres() ? 'false' : 0))
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
            throw $e;
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
     * Get event address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Get event zip
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Get event town
     *
     * @return string
     */
    public function getTown()
    {
        return $this->town;
    }

    /**
     * Get event country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Get event group
     *
     * @return integer
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Get date
     *
     * @param string  $prop      Property to use
     * @param boolean $formatted Return date formatted, raw if false
     *
     * @return string
     */
    private function getDate($prop, $formatted = true)
    {
        if ($formatted === true) {
            $date = new \DateTime($this->$prop);
            return $date->format(__("Y-m-d"));
        } else {
            return $this->$prop;
        }
    }

    /**
     * Get creation date
     *
     * @param boolean $formatted Return date formatted, raw if false
     *
     * @return string
     */
    public function getCreationDate($formatted = true)
    {
        return $this->getDate('creation_date', $formatted);
    }

    /**
     * Get begin date
     *
     * @param boolean $formatted Return date formatted, raw if false
     *
     * @return string
     */
    public function getBeginDate($formatted = true)
    {
        return $this->getDate('begin_date', $formatted);
    }

    /**
     * Get end date
     *
     * @param boolean $formatted Return date formatted, raw if false
     *
     * @return string
     */
    public function getEndDate($formatted = true)
    {
        return $this->getDate('end_date', $formatted);
    }

    /**
     * Does event includes a meal?
     *
     * @return boolean
     */
    public function hasMeal()
    {
        return $this->meal;
    }

    /**
     * Is meal required?
     *
     * @return boolean
     */
    public function isMealRequired()
    {
        if ($this->hasMeal()) {
            return $this->meal_required;
        }
        return false;
    }

    /**
     * Does event includes a lodging?
     *
     * @return boolean
     */
    public function hasLodging()
    {
        return $this->lodging;
    }

    /**
     * Is lodging required?
     *
     * @return boolean
     */
    public function isLodgingRequired()
    {
        if ($this->hasLodging()) {
            return $this->lodging_required;
        }
        return false;
    }

    /**
     * Is event open?
     * Will return false once the begin date has been exceeded
     *
     * @return boolean
     */
    public function isOpen()
    {
        if ($this->open) {
            try {
                $date = new \DateTime($this->begin_date);
                $now  = new \DateTime();
                return $date > $now;
            } catch (\Exception $e) {
                //no begin date, or invalid date...
                return true;
            }
        }
        return false;
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
