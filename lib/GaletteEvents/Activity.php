<?php

/**
 * Copyright © 2003-2024 The Galette Team
 *
 * This file is part of Galette (https://galette.eu).
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
 */

namespace GaletteEvents;

use ArrayObject;
use Galette\Core\Db;
use Galette\Core\Login;
use Analog\Analog;
use Laminas\Db\Sql\Expression;

/**
 * Activity entity
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Activity
{
    public const TABLE = 'activities';
    public const PK = 'id_activity';

    public const NO = 0;
    public const YES = 1;
    public const REQUIRED = 2;

    private Db $zdb;
    private Login $login;
    /** @var array<string> */
    private array $errors;

    private int $id;
    private string $name;
    private bool $active = true;
    private string $creation_date;
    private string $comment;

    /**
     * Default constructor
     *
     * @param Db                                      $zdb   Database instance
     * @param Login                                   $login Login instance
     * @param null|int|ArrayObject<string,int|string> $args  Either a ResultSet row or its id for to load
     *                                                       a specific event, or null to just
     *                                                       instanciate object
     */
    public function __construct(Db $zdb, Login $login, int|ArrayObject $args = null)
    {
        $this->zdb = $zdb;
        $this->login = $login;

        if (is_int($args) && $args > 0) {
            $this->load($args);
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
    public function load(int $id): bool
    {
        try {
            $select = $this->zdb->select($this->getTableName());
            $select->where(array(self::PK => $id));
            $results = $this->zdb->execute($select);

            if ($results->count() > 0) {
                $this->loadFromRS($results->current());
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Analog::log(
                'Cannot load activity #`' . $id . '` | ' . $e->getMessage(),
                Analog::WARNING
            );
            throw $e;
        }
    }

    /**
     * Populate object from a resultset row
     *
     * @param ArrayObject<string, string|int> $r the resultset row
     *
     * @return void
     */
    private function loadFromRS(ArrayObject $r): void
    {
        $this->id = $r->id_activity;
        $this->name = $r->name;
        $this->active = $r->is_active;
        $this->creation_date = $r->creation_date;
        $this->comment = $r->comment;
    }

    /**
     * Remove specified event
     *
     * @return boolean
     */
    public function remove(): bool
    {
        $transaction = false;

        try {
            if (!$this->zdb->connection->inTransaction()) {
                $this->zdb->connection->beginTransaction();
                $transaction = true;
            }

            $delete = $this->zdb->delete($this->getTableName());
            $delete->where([self::PK => $this->id]);
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
                'Unable to delete activity ' . $this->name .
                ' (' . $this->id  . ') |' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Check posted values validity
     *
     * @param array<string, mixed> $values All values to check, basically the $_POST array
     *                                     after sending the form
     *
     * @return true|array<string>
     */
    public function check(array $values): bool|array
    {
        $this->errors = array();

        if (empty($values['name'])) {
            $this->errors[] = _T('Name is mandatory', 'events');
        } else {
            $this->name = $values['name'];
        }

        if (isset($values['active'])) {
            $this->active = true;
        } else {
            $this->active = false;
        }

        if (isset($values['comment'])) {
            $this->comment = $values['comment'];
        }

        if (count($this->errors) > 0) {
            Analog::log(
                'Some errors has been throwed attempting to edit/store an activity' . "\n" .
                print_r($this->errors, true),
                Analog::ERROR
            );
            return $this->errors;
        } else {
            Analog::log(
                'Activity checked successfully.',
                Analog::DEBUG
            );
            return true;
        }
    }

    /**
     * Store the activity
     *
     * @return boolean
     */
    public function store(): bool
    {
        global $hist;

        try {
            $values = array(
                self::PK                => $this->id,
                'name'                  => $this->name,
                'is_active'             => ($this->active ? $this->active :
                                                ($this->zdb->isPostgres() ? 'false' : 0)),
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
                        /** @phpstan-ignore-next-line */
                        $this->id = $this->zdb->driver->getLastGeneratedValue(
                            PREFIX_DB . $this->getTableName() . '_id_seq'
                        );
                    } else {
                        $this->id = $this->zdb->driver->getLastGeneratedValue();
                    }

                    // logging
                    $hist->add(
                        _T("Activity added", "events"),
                        $this->name
                    );
                    return true;
                } else {
                    $hist->add(_T("Fail to add new activity.", "events"));
                    throw new \Exception(
                        'An error occurred inserting new activity!'
                    );
                }
            } else {
                //we're editing an existing event
                $update = $this->zdb->update($this->getTableName());
                $update
                    ->set($values)
                    ->where([self::PK => $this->id]);

                $edit = $this->zdb->execute($update);

                //edit == 0 does not mean there were an error, but that there
                //were nothing to change
                if ($edit->count() > 0) {
                    $hist->add(
                        _T("Activity updated", "events"),
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
        }
    }

    /**
     * Get event id
     *
     * @return ?integer
     */
    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * Get date
     *
     * @param string  $prop      Property to use
     * @param boolean $formatted Return date formatted, raw if false
     *
     * @return string
     */
    private function getDate(string $prop, bool $formatted = true): string
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
    public function getCreationDate(bool $formatted = true): string
    {
        return $this->getDate('creation_date', $formatted);
    }

    /**
     * Is actvity active?
     *
     * @return boolean
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Set name
     *
     * @param string $name Activity name
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get table's name
     *
     * @return string
     */
    protected function getTableName(): string
    {
        return EVENTS_PREFIX  . self::TABLE;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment ?? '';
    }

    /**
     * Count number of events using this Activity
     *
     * @return integer
     */
    public function countEvents(): int
    {
        $select = $this->zdb->select(EVENTS_PREFIX . 'activitiesevents');

        $select->columns(
            array(
                'counter' => new Expression('COUNT(' . Event::PK . ')')
            )
        )->where([self::PK => $this->id]);
        $results = $this->zdb->execute($select);
        $result = $results->current();
        $count = $result->counter;
        return $count;
    }
}
