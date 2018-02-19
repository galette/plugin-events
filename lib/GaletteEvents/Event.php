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
 * @copyright 2018 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */

namespace GaletteEvents;

use Galette\Core\Db;
use Galette\Core\Login;
use Galette\Entity\Group;
use Galette\Repository\Groups;
use Analog\Analog;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Predicate;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Db\Sql\Predicate\Operator;

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

    const ACTIVITY_NO = 0;
    const ACTIVITY_YES = 1;
    const ACTIVITY_REQUIRED = 2;

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
    private $open = true;
    private $group;
    private $comment = '';

    private $activities = [];

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
            $this->loadActivities();
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
                $groups = Groups::loadGroups(
                    $this->login->id,
                    false,
                    false
                );

                if ($this->login->isGroupManager() && count($this->login->managed_groups)) {
                    $groups = array_merge($groups, $this->login->managed_groups);
                }

                $select->where(
                    new PredicateSet(
                        array(
                            new Predicate\In(
                                Group::PK,
                                $groups
                            ),
                            new Predicate\IsNull(Group::PK)
                        ),
                        PredicateSet::OP_OR
                    )
                );
            }
            $results = $this->zdb->execute($select);

            if ($results->count() > 0) {
                $this->loadFromRS($results->current());
                $this->loadActivities();
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
        $this->noon_meal = $r->noon_meal;
        $this->even_meal = $r->even_meal;
        $this->lodging = $r->lodging;
        $this->open = $r->is_open;
        $this->group = $r->id_group;
        $this->comment = $r->comment;
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
            'comment'
        ];
        foreach ($otherfields as $otherfield) {
            if (isset($values[$otherfield])) {
                $this->$otherfield = $values[$otherfield];
            }
        }

        if (isset($values['add_activity'])
            && isset($values['attach_activity'])
            && !empty($values['attach_activity'])
        ) {
            $this->activities[$values['attach_activity']] = [
                'activity'  => new Activity(
                    $this->zdb,
                    $this->login,
                    (int)$values['attach_activity']
                ),
                'status'    => Activity::YES
            ];
        }

        if (isset($values['remove_activity'])
            && isset($values['detach_activity'])
            && !empty($values['detach_activity'])
        ) {
            unset($this->activities[$values['detach_activity']]);
            if (count($values['activities_ids'])) {
                unset($values['activities_ids'][array_search($values['detach_activity'], $values['activities_ids'])]);
            }
        }

        if (isset($values['activities_ids'])) {
            foreach ($values['activities_ids'] as $row => $activity_id) {
                if (isset($this->activities[$activity_id])) {
                    $this->activities[$activity_id]['status'] = $values['activities_status'][$row];
                } else {
                    $activity = new Activity($this->zdb, $this->login, (int)$activity_id);
                    $this->activities[$activity_id] = [
                        'activity'  => $activity,
                        'status'    => $values['activities_status'][$row]
                    ];
                }
            }
        }

        if (isset($values['open'])) {
            $this->open = true;
        } else {
            $this->open = false;
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
            $this->zdb->connection->beginTransaction();
            $values = array(
                self::PK                => $this->id,
                'name'                  => $this->name,
                'address'               => $this->address,
                'zip'                   => $this->zip,
                'town'                  => $this->town,
                'country'               => ($this->country ? $this->country : new Expression('NULL')),
                'begin_date'            => $this->begin_date,
                'end_date'              => $this->end_date,
                'is_open'               => ($this->open ? $this->open :
                                                ($this->zdb->isPostgres() ? 'false' : 0)),
                Group::PK               => ($this->group ? $this->group : new Expression('NULL')),
                'comment'               => $this->comment
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
                            PREFIX_DB . EVENTS_PREFIX . Event::TABLE . '_id_seq'
                        );
                    } else {
                        $this->id = $this->zdb->driver->getLastGeneratedValue();
                    }

                    // logging
                    $hist->add(
                        _T("Event added", "events"),
                        $this->name
                    );
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
            }

            $void   = [];
            $update = [];
            $insert = [];
            $delete = [];

            foreach ($this->activities as $aid => $data) {
                $activity = $data['activity'];
                $status = $data['status'];
                $key_values = [
                    self::PK        => $this->id,
                    $activity::PK   => $activity->getId()
                ];

                $select = $this->zdb->select(EVENTS_PREFIX . 'activitiesevents', 'ace');
                $select->where($key_values);
                $results = $this->zdb->execute($select);

                foreach ($results as $result) {
                    $values = [
                        Activity::PK    => $result[Activity::PK],
                        self::PK        => $this->id,
                        'status'        => $status
                    ];
                    if (!isset($this->activities[$result[Activity::PK]])) {
                        $delete[$result[Activity::PK]] = $values;
                    } elseif ($result['status'] != $this->activities[$result[Activity::PK]]['status']) {
                        $update[$result[Activity::PK]] = $values;
                    } else {
                        $void[$result[Activity::PK]] = $values;
                    }
                }

                if (!isset($void[$aid]) && !isset($update[$aid]) && !isset($delete[$aid])) {
                    $insert[$aid] = [
                        Activity::PK    => $aid,
                        self::PK        => $this->id,
                        'status'        => $status
                    ];
                }
            }

            if (count($delete)) {
                $stmt = $this->zdb->delete(EVENTS_PREFIX . 'activitiesevents', 'ace');
                $count = 0;
                foreach ($delete as $values) {
                    $stmt->where($values);
                    $this->zdb->execute($stmt);
                    ++$count;
                }
                Analog::log(
                    str_replace('%count', $count, '%count activities removed'),
                    Analog::INFO
                );
            }

            if (count($update)) {
                $stmt = $this->zdb->update(EVENTS_PREFIX . 'activitiesevents', 'ace');
                $count = 0;
                foreach ($update as $values) {
                    $stmt
                        ->set($values)
                        ->where($key_values);
                    $this->zdb->execute($stmt);
                    ++$count;
                }
                Analog::log(
                    str_replace('%count', $count, '%count activities updated'),
                    Analog::INFO
                );
            }

            if (count($insert)) {
                $stmt = $this->zdb->insert(EVENTS_PREFIX . 'activitiesevents', 'ace');
                $count = 0;
                foreach ($insert as $values) {
                    $stmt->values(array_merge($key_values, $values));
                    $this->zdb->execute($stmt);
                    ++$count;
                }
                Analog::log(
                    str_replace('%count', $count, '%count activities added'),
                    Analog::INFO
                );
            }

            $this->zdb->connection->commit();
            return true;
        } catch (\Exception $e) {
            $this->zdb->connection->rollBack();
            Analog::log(
                'Something went wrong :\'( | ' . $e->getMessage() . "\n" .
                $e->getTraceAsString(),
                Analog::ERROR
            );
            throw $e;
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
     * Get group name
     *
     * @return string
     */
    public function getGroupName()
    {
        $name = '-';
        if ($this->group) {
            $group = new Group((int)$this->group);
            $name = $group->getFullName();
        }
        return $name;
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
     * Is activity required
     *
     * @param integer $activity Activity ID
     *
     * @return boolean
     */
    public function isActivityRequired($activity)
    {
        return $this->activities[$activity]['status'] == Activity::REQUIRED;
    }

    /**
     * Does current event propose activity
     *
     * @param integer $activity Activity ID
     *
     * @return boolean
     */
    public function hasActivity($activity)
    {
        return $this->activities[$activity]['status'] != Activity::NO;
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
                $now->setTime(0, 0);
                return $date >= $now;
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

    /**
     * Get activities list
     *
     * @return array
     */
    public function availableActivities()
    {
        $select = $this->zdb->select(EVENTS_PREFIX . Activity::TABLE, 'ac');
        $results = $this->zdb->execute($select);

        $activities = [];
        foreach ($results as $result) {
            if (!isset($this->activities[$result->{Activity::PK}])) {
                $activities[] = $result;
            }
        }

        return $activities;
    }

    /**
     * Load linked activities
     *
     * @return void
     */
    public function loadActivities()
    {
        $select = $this->zdb->select(EVENTS_PREFIX . 'activitiesevents', 'ace');
        $select->where([self::PK => $this->id]);
        $results = $this->zdb->execute($select);
        foreach ($results as $result) {
            $this->activities[$result[Activity::PK]] = [
                'activity'  => new Activity(
                    $this->zdb,
                    $this->login,
                    (int)$result[Activity::PK]
                ),
                'status'    => $result['status']
            ];
        }
    }


    /**
     * Get linked activities
     *
     * @return array
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }
}
