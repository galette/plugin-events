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

namespace GaletteEvents\Repository;

use Analog\Analog;
use Galette\Repository\Repository;
use GaletteEvents\Activity;
use Galette\Core\Preferences;
use GaletteEvents\Filters\ActivitiesList;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Expression;
use Galette\Core\Login;
use Galette\Core\Db;
use GaletteEvents\Filters\EventsList;
use Laminas\Db\Sql\Select;
use stdClass;

/**
 * Events
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Activities extends Repository
{
    private int $count;

    public const ORDERBY_DATE = 0;
    public const ORDERBY_NAME = 1;

    /**
     * Constructor
     *
     * @param Db              $zdb         Database instance
     * @param Login           $login       Login instance
     * @param Preferences     $preferences Preferences instance
     * @param ?ActivitiesList $filters     Filtering
     */
    public function __construct(Db $zdb, Login $login, Preferences $preferences, ActivitiesList $filters = null)
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
     * @return array<int, Activity>|ResultSet
     */
    public function getList(): array|ResultSet
    {
        try {
            $select = $this->zdb->select(EVENTS_PREFIX . Activity::TABLE, 'ac');
            $select->order($this->buildOrderClause());

            $this->proceedCount($select);

            $this->filters->setLimits($select);
            $results = $this->zdb->execute($select);
            //@phpstan-ignore-next-line
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
     * @param ?array $fields Fields list to ensure ORDER clause
     *                       references selected fields. Optional.
     *
     * @return array<string> SQL ORDER clauses
     */
    private function buildOrderClause(array $fields = null): array
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
    private function proceedCount(Select $select): void
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
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Add default activities in database
     *
     * @param boolean $check_first Check first if it seems initialized
     *
     * @return boolean
     */
    public function installInit(bool $check_first = true): bool
    {
        //to satisfy inheritance
        return true;
    }

    /**
     * Insert values in database
     *
     * @param string               $table  Table name
     * @param array<string, mixed> $values Values to insert
     *
     * @return void
     */
    protected function insert(string $table, array $values): void
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
}
