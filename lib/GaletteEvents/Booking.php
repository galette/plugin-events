<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Booking entity
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
use Galette\Entity\Adherent;
use Galette\Entity\Contribution;
use Analog\Analog;
use Zend\Db\Sql\Expression;

/**
 * Booking entity
 *
 * @category  Entity
 * @name      Event
 * @package   GaletteEvents
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2018 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */
class Booking
{
    const TABLE = 'bookings';
    const PK = 'id_booking';

    private $zdb;
    private $login;
    private $errors;

    private $id;
    private $event;
    private $member;
    private $date;
    private $paid;
    private $amount;
    private $payment_method = Contribution::PAYMENT_OTHER;
    private $bank_name;
    private $check_number;
    private $number_people = 1;
    private $comment = '';

    private $activities = [];
    private $activities_removed = [];

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
            $this->load($args);
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
                'Cannot load booking form id `' . $id . '` | ' . $e->getMessage(),
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
        $this->id = $r->id_booking;
        $this->event = $r->id_event;
        $this->member = $r->id_adh;
        $this->date = $r->booking_date;
        $this->paid = $r->is_paid;
        $this->amount = $r->payment_amount;
        $this->payment_method = $r->payment_method;
        $this->bank_name = $r->bank_name;
        $this->check_number = $r->check_number;
        $this->number_people = $r->number_people;
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
                'Unable to delete booking ' . $this->name .
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

        if (!isset($values['event']) || empty($values['event'])) {
            $this->errors[] = _T('Event is mandatory', 'events');
        } else {
            $this->event = $values['event'];
            $event = $this->getEvent();
            $activities = $event->getActivities();
            foreach ($activities as $aid => $entry) {
                if ($event->isActivityRequired($aid)
                    && (!isset($values['activities']) || !in_array($aid, $values['activities']))
                ) {
                    $this->errors[] = str_replace(
                        '%activity',
                        $entry['activity']->getName(),
                        _T('%activity is mandatory for this event!', 'events')
                    );
                } else {
                    $act = [
                        'activity'  => $entry['activity'],
                        'checked'   => (isset($values['activities']) && in_array($aid, $values['activities']))
                    ];
                    $this->activities[$aid] = $act;
                }
            }
            foreach (array_keys($this->activities) as $aid) {
                if (!isset($activities[$aid])) {
                    $this->activities_removed[$aid] = [
                        Activity::PK    => $aid,
                        self::PK        => $this->id
                    ];
                    unset($this->activities[$aid]);
                }
            }
        }

        if (!isset($values['member']) || empty($values['member'])) {
            $this->errors[] = _T('Member is mandatory', 'events');
        } else {
            $this->member = $values['member'];
        }

        if (isset($values['paid'])) {
            $this->paid = true;
        } else {
            $this->paid = false;
        }

        if (isset($values['amount']) && !empty($values['amount'])) {
            $this->amount = $values['amount'];
        }

        if ($this->paid && !$this->amount) {
            $this->errors[] = _T('Please specify amount if booking has been paid ;)', 'events');
        }

        if (isset($values['payment_method'])) {
            $this->payment_method = $values['payment_method'];
        }

        if (isset($values['bank_name'])) {
            $this->bank_name = $values['bank_name'];
        }

        if (isset($values['check_number'])) {
            $this->check_number = $values['check_number'];
        }

        if (isset($values['number_people'])) {
            if ((int)$values['number_people'] > 0) {
                $this->number_people = $values['number_people'];
            } else {
                $this->errors[] = _T('There must be at least one person', 'events');
            }
        }

        if (isset($values['comment'])) {
            $this->comment = $values['comment'];
        }

        if (!isset($values['booking_date']) || empty($values['booking_date'])) {
            $this->errors[] = _T('Booking date is mandatory!', 'events');
        } else {
            $value = $values['booking_date'];
            try {
                $d = \DateTime::createFromFormat(__("Y-m-d"), $value);
                if ($d === false) {
                    //try with non localized date
                    $d = \DateTime::createFromFormat("Y-m-d", $value);
                    if ($d === false) {
                        throw new \Exception('Incorrect format');
                    }
                }
                $this->booking_date = $d->format('Y-m-d');
            } catch (\Exception $e) {
                Analog::log(
                    'Wrong date format. field: booking_date' .
                    ', value: ' . $value . ', expected fmt: ' .
                    __("Y-m-d") . ' | ' . $e->getMessage(),
                    Analog::INFO
                );
                $this->errors[] = str_replace(
                    array(
                        '%date_format',
                        '%field'
                    ),
                    array(
                        __("Y-m-d"),
                        __('booking date', 'events')
                    ),
                    _T("- Wrong date format (%date_format) for %field!")
                );
            }
        }

        if (count($this->errors) == 0) {
            //check unicity
            $select = $this->zdb->select($this->getTableName());
            $select->where([
                Event::PK       => $this->event,
                Adherent::PK    => $this->member
            ]);
            if ($this->id) {
                $select->where->notEqualTo(
                    self::PK,
                    $this->id
                );
            }
            $results = $this->zdb->execute($select);
            if ($results->count()) {
                $this->errors[] = str_replace(
                    [
                        '%member',
                        '%event'
                    ],
                    [
                        $this->getMember()->sfullname,
                        $this->getEvent()->getName()
                    ],
                    _T('A booking already exists for %member in %event', 'events')
                );
            }
        }

        if (count($this->errors) > 0) {
            Analog::log(
                'Some errors has been throwed attempting to edit/store a booking' . "\n" .
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
     * Store the groupevent
     *
     * @return boolean
     */
    public function store()
    {
        global $hist;

        try {
            $this->zdb->connection->beginTransaction();
            $values = array(
                self::PK            => $this->id,
                Event::PK           => $this->event,
                Adherent::PK        => $this->member,
                'booking_date'      => $this->booking_date,
                'is_paid'           => ($this->paid ? $this->paid :
                                            ($this->zdb->isPostgres() ? 'false' : 0)),
                'payment_method'    => $this->payment_method,
                'payment_amount'    => $this->amount,
                'bank_name'         => $this->bank_name,
                'check_number'      => $this->check_number,
                'number_people'     => $this->number_people,
                'comment'           => $this->comment
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
                            PREFIX_DB . EVENTS_PREFIX . Booking::TABLE . '_id_seq'
                        );
                    } else {
                        $this->id = $this->zdb->driver->getLastGeneratedValue();
                    }

                    // logging
                    $hist->add(
                        _T("Booking added", "events"),
                        $this->getEvent()->getName()
                    );
                } else {
                    $hist->add(_T("Fail to add new booking.", "events"));
                    throw new \Exception(
                        'An error occured inserting new booking!'
                    );
                }
            } else {
                //we're editing an existing booking
                $update = $this->zdb->update($this->getTableName());
                $update
                    ->set($values)
                    ->where(self::PK . '=' . $this->id);

                $edit = $this->zdb->execute($update);

                //edit == 0 does not mean there were an error, but that there
                //were nothing to change
                if ($edit->count() > 0) {
                    $hist->add(
                        _T("Booking updated", "events")
                    );
                }
            }

            //store booking activities
            $void   = [];
            $update = [];
            $insert = [];
            $delete = $this->activities_removed;

            foreach ($this->activities as $aid => $data) {
                $activity = $data['activity'];
                $checked = $data['checked'];
                $key_values = [
                    self::PK        => $this->id,
                    $activity::PK   => $activity->getId()
                ];

                $select = $this->zdb->select(EVENTS_PREFIX . 'activitiesbookings', 'acb');
                $select->where($key_values);
                $results = $this->zdb->execute($select);

                foreach ($results as $result) {
                    if (!isset($this->activities[$result[Activity::PK]])) {
                        $delete[$result[Activity::PK]] = [
                            Activity::PK    => $result[Activity::PK],
                            self::PK        => $this->id,
                        ];
                    } elseif ($result['checked'] != $this->activities[$result[Activity::PK]]['checked']) {
                        $update[$result[Activity::PK]] = [
                            'checked'   => ($checked ? $checked :
                                            ($this->zdb->isPostgres() ? 'false' : 0))
                        ];
                    } else {
                        $void[$result[Activity::PK]] = true;
                    }
                }

                if (!isset($void[$aid]) && !isset($update[$aid]) && !isset($delete[$aid])) {
                    $insert[$aid] = [
                        Activity::PK    => $aid,
                        self::PK        => $this->id,
                        'checked'       => ($checked ? $checked :
                                            ($this->zdb->isPostgres() ? 'false' : 0))
                    ];
                }
            }

            if (count($delete)) {
                $prepare = $this->zdb->delete(EVENTS_PREFIX . 'activitiesbookings', 'acb');
                $prepare->where([
                    self::PK        => $this->id,
                    $activity::PK   => ':aid'
                ]);
                $stmt = $this->zdb->sql->prepareStatementForSqlObject($prepare);

                $count = 0;
                foreach ($delete as $values) {
                    $stmt->execute([':aid' => $value[$activity::PK]]);
                    ++$count;
                }
                Analog::log(
                    str_replace('%count', $count, '%count activities removed'),
                    Analog::INFO
                );
            }

            if (count($update)) {
                $prepare = $this->zdb->update(EVENTS_PREFIX . 'activitiesbookings', 'acb');
                $prepare->set([
                    'checked'       => ':checked'
                ])->where([
                    self::PK        => $this->id,
                    $activity::PK   => ':aid'
                ]);
                $stmt = $this->zdb->sql->prepareStatementForSqlObject($prepare);
                $count = 0;
                foreach ($update as $aid => $values) {
                    $params = [
                        'where2'    => $aid,
                        ':checked'  => $values['checked']
                    ];
                    $stmt->execute($params);
                    ++$count;
                }
                Analog::log(
                    str_replace('%count', $count, '%count activities updated'),
                    Analog::INFO
                );
            }

            if (count($insert)) {
                $prepare = $this->zdb->insert(EVENTS_PREFIX . 'activitiesbookings', 'acb');
                $prepare->values([
                    self::PK        => ':id',
                    $activity::PK   => ':aid',
                    'checked'       => ':checked'
                ]);
                $stmt = $this->zdb->sql->prepareStatementForSqlObject($prepare);
                $count = 0;
                foreach ($insert as $aid => $values) {
                    $params = [
                        $this->id,
                        $aid,
                        $values['checked']
                    ];
                    $stmt->execute($params);
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
     * Get event id
     *
     * @return integer
     */
    public function getEventId()
    {
        return $this->event;
    }

    /**
     * Get event
     *
     * @return Event
     */
    public function getEvent()
    {
        return new Event($this->zdb, $this->login, (int)$this->event);
    }

    /**
     * Get member id
     *
     * @return integer
     */
    public function getMemberId()
    {
        return $this->member;
    }

    /**
     * Get member
     *
     * @return Adherent
     */
    public function getMember()
    {
        return new Adherent($this->zdb, (int)$this->member);
    }

    /**
     * Get date
     *
     * @param boolean $formatted Return date formatted, raw if false
     *
     * @return string
     */
    public function getDate($formatted = true)
    {
        if ($formatted === true) {
            $date = new \DateTime($this->date);
            return $date->format(__("Y-m-d"));
        } else {
            return $this->date;
        }
    }

    /**
     * Is booking paid?
     *
     * @return boolean
     */
    public function isPaid()
    {
        return $this->paid;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Get payment method
     *
     * @return integer
     */
    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    /**
     * Get payment method name
     *
     * @return string
     */
    public function getPaymentMethodName()
    {
        switch ($this->payment_method) {
            case Contribution::PAYMENT_CASH:
                return _T('Cash');
                break;
            case Contribution::PAYMENT_CREDITCARD:
                return _T('Credit card');
                break;
            case Contribution::PAYMENT_CHECK:
                return _T('Check');
                break;
            case Contribution::PAYMENT_TRANSFER:
                return _T('Transfer');
                break;
            case Contribution::PAYMENT_PAYPAL:
                return _T('Paypal');
                break;
            case Contribution::PAYMENT_OTHER:
                return _T('Other');
                break;
            default:
                return '';
                break;
        }
    }

    /**
     * Get bank name
     *
     * @return string
     */
    public function getBankName()
    {
        return $this->bank_name;
    }

    /**
     * Get check number
     *
     * @return string
     */
    public function getCheckNumber()
    {
        return $this->check_number;
    }

    /**
     * Get number of persons
     *
     * @return integer
     */
    public function getNumberPeople()
    {
        return $this->number_people;
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
        if ($formatted === true) {
            $date = new \DateTime($this->creation_date);
            return $date->format(__("Y-m-d"));
        } else {
            return $this->creation_date;
        }
    }

    /**
     * Set event
     *
     * @param integer $event Event id
     *
     * @return Booking
     */
    public function setEvent($event)
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Set member
     *
     * @param integer $member Member id
     *
     * @return Booking
     */
    public function setMember($member)
    {
        $this->member = $member;
        return $this;
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
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Has Activity
     *
     * @param string $activity Activity
     *
     * @return boolean
     */
    public function has($activity)
    {
        return isset($this->activities[$activity]) && $this->activities[$activity]['checked'];
    }

    /**
     * Load linked activities
     *
     * @return void
     */
    public function loadActivities()
    {
        $select = $this->zdb->select(EVENTS_PREFIX . 'activitiesbookings', 'acb');
        $select->where([self::PK => $this->id]);
        $results = $this->zdb->execute($select);
        foreach ($results as $result) {
            $this->activities[$result[Activity::PK]] = [
                'activity'  => new Activity(
                    $this->zdb,
                    $this->login,
                    (int)$result[Activity::PK]
                ),
                'checked'    => $result['checked']
            ];
        }
    }

    /**
     * Get activities
     *
     * @return array
     */
    public function getActivities()
    {
        return $this->activities;
    }
}
