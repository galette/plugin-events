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
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Predicate;
use Laminas\Db\Sql\Predicate\PredicateSet;
use Laminas\Db\Sql\Predicate\Operator;
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
class Events
{
    private $zdb;
    private $login;
    private $filters = false;
    private $count;

    const ORDERBY_DATE = 0;
    const ORDERBY_NAME = 1;
    const ORDERBY_TOWN = 2;

    /**
     * Constructor
     *
     * @param Db         $zdb     Database instance
     * @param Login      $login   Login instance
     * @param EventsList $filters Filtering
     */
    public function __construct(Db $zdb, Login $login, $filters = null)
    {
        $this->zdb = $zdb;
        $this->login = $login;

        if ($filters === null) {
            $this->filters = new EventsList();
        } else {
            $this->filters = $filters;
        }
    }

    /**
     * Get events list
     *
     * @return GaletteEvents\Event[]
     */
    public function getList()
    {
        try {
            $select = $this->zdb->select(EVENTS_PREFIX . Event::TABLE, 'e');

            $groups = null;
            if (!$this->login->isAdmin() && !$this->login->isStaff()) {
                if ($this->login->isGroupManager()) {
                    $groups = $this->login->managed_groups;
                    $set = [new PredicateSet(
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
                    )];

                    if (count($groups)) {
                        $set[] = new Predicate\In(
                            Group::PK,
                            $groups
                        );
                    }
                    $select->where(
                        new PredicateSet(
                            $set,
                            PredicateSet::OP_OR
                        )
                    );
                } else {
                    $select->where('is_open', true);
                    $select->where->greaterThanOrEqualTo('begin_date', date('Y-m-d'));

                    $set = [new Predicate\IsNull(Group::PK)];
                    $groups = Groups::loadGroups($this->login->id, false, false);
                    if (count($groups)) {
                        $set[] = new Predicate\In(
                            Group::PK,
                            $groups
                        );
                    }

                    $select->where(
                        new PredicateSet(
                            $set,
                            PredicateSet::OP_OR
                        )
                    );
                }
            }

            $select->order($this->buildOrderClause());

            $this->proceedCount($select);

            if (!$this->filters->calendar_filter) {
                $this->filters->setLimits($select);
            }
            $results = $this->zdb->execute($select);
            $this->filters->query = $this->zdb->query_string;

            $events = [];
            foreach ($results as $row) {
                $event = new Event($this->zdb, $this->login, $row);
                if (!$this->filters->calendar_filter) {
                    $events[] = $event;
                } else {
                    //required entries for fullcalendar
                    $row['title'] = $row['name'];
                    $row['start'] = $row['begin_date'];
                    $row['end'] = $row['end_date'];

                    //extended description
                    $row['end_date_fmt'] = $event->getBeginDate();
                    $row['begin_date_fmt'] = $event->getEndDate();
                    $description = '<h4><a href="" id="event_link">' . _T('Event informations', 'events');
                    $description .= '&nbsp;<i class="fas fa-eye"></i></a></h4>';
                    $description .= '<ul>';
                    $pattern = '<li><strong>%1$s</strong> %2$s</li>';
                    $description .= sprintf($pattern, _T("Start date:", "events"), $event->getBeginDate());
                    $description .= sprintf($pattern, _T("End date:", "events"), $event->getEndDate());
                    $description .= sprintf($pattern, _T("Location:", "events"), $event->getTown());
                    if ($comment = $event->getComment()) {
                        $description .= sprintf($pattern, _T("Comment:", "events"), $comment);
                    }
                    $description .= sprintf($pattern, _T("Attendees:", "events"), $event->countAttendees());
                    $description .= '</ul>';

                    $activities = $event->getActivities();
                    if (count($activities)) {
                        $description .= '<h4>' . _T('Activities', 'events')  . '</h4>';
                        $description .= '<ul>';
                        foreach ($activities as $activity) {
                            $description .= '<li>' . $activity['activity']->getName()  . '</li>';
                        }
                        $description .= '</ul>';
                    }

                    $row['description'] = $description;

                    $events[] = $row;
                }
            }

            return $events;
        } catch (\Exception $e) {
            Analog::log(
                'Cannot list events | ' . $e->getMessage(),
                Analog::WARNING
            );
            throw $e;
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
            case self::ORDERBY_DATE:
                if ($this->canOrderBy('begin_date', $fields)) {
                    $order[] = 'begin_date ' . $this->filters->getDirection();
                }
                break;
            case self::ORDERBY_NAME:
                if ($this->canOrderBy('name', $fields)) {
                    $order[] = 'name ' . $this->filters->getDirection();
                }
                break;
            case self::ORDERBY_TOWN:
                if ($this->canOrderBy('town', $fields)) {
                    $order[] = 'town ' . $this->filters->getDirection();
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
                    'count' => new Expression('count(DISTINCT e.' . Event::PK . ')')
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
                'Cannot count events | ' . $e->getMessage(),
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
}
