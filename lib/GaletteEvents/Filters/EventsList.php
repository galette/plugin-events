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

declare(strict_types=1);

namespace GaletteEvents\Filters;

use Analog\Analog;
use Galette\Core\Pagination;
use GaletteEvents\Repository\Events;

/**
 * Events lists filters and paginator
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 *
 * @property string $query
 * @property bool $calendar_filter
 */

class EventsList extends Pagination
{
    //filters
    private ?string $name_filter = null; //@phpstan-ignore-line
    private ?string $start_date_filter = null;
    private ?string $end_date_filter = null;
    private int $group_filter = 0;
    private ?string $meal_filter = null; //@phpstan-ignore-line
    private ?string $lodging_filter = null; //@phpstan-ignore-line
    private ?string $open_filter = null; //@phpstan-ignore-line
    private bool $calendar_filter = false;
    private string $query;

    /** @var array<string> */
    protected array $list_fields = array(
        'name_filter',
        'start_date_filter',
        'raw_start_date_filter',
        'end_date_filter',
        'raw_end_date_filter',
        'group_filter',
        'meal_filter',
        'lodging_filter',
        'open_filter',
        'calendar_filter'
    );

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->reinit();
    }

    /**
     * Returns the field we want to default set order to
     *
     * @return int|string field name
     */
    protected function getDefaultOrder(): int|string
    {
        return Events::ORDERBY_DATE;
    }

    /**
     * Return the default direction for ordering
     *
     * @return string ASC or DESC
     */
    protected function getDefaultDirection(): string
    {
        return self::ORDER_DESC;
    }

    /**
     * Reinit default parameters
     *
     * @return void
     */
    public function reinit(): void
    {
        parent::reinit();
        $this->name_filter = null;
        $this->start_date_filter = null;
        $this->end_date_filter = null;
        $this->group_filter = 0;
        $this->meal_filter = null;
        $this->lodging_filter = null;
        $this->open_filter = null;
        $this->calendar_filter = false;
    }

    /**
     * Global getter method
     *
     * @param string $name name of the property we want to retrieve
     *
     * @return mixed the called property
     */
    public function __get(string $name): mixed
    {
        if (in_array($name, $this->pagination_fields)) {
            return parent::__get($name);
        } else {
            if (in_array($name, $this->list_fields)) {
                switch ($name) {
                    case 'raw_start_date_filter':
                        return $this->start_date_filter;
                    case 'raw_end_date_filter':
                        return $this->end_date_filter;
                    case 'start_date_filter':
                    case 'end_date_filter':
                        try {
                            if ($this->$name !== null) {
                                $d = new \DateTime($this->$name);
                                return $d->format(__("Y-m-d"));
                            }
                        } catch (\Exception $e) {
                            //oops, we've got a bad date :/
                            Analog::log(
                                'Bad date (' . $this->$name . ') | ' .
                                $e->getMessage(),
                                Analog::INFO
                            );
                            return $this->$name;
                        }
                        break;
                    default:
                        return $this->$name;
                }
            }
        }

        throw new \RuntimeException(
            sprintf(
                'Unable to get property "%s::%s"!',
                __CLASS__,
                $name
            )
        );
    }

    /**
     * Global setter method
     *
     * @param string $name  name of the property we want to assign a value to
     * @param mixed  $value a relevant value for the property
     *
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        if (in_array($name, $this->pagination_fields)) {
            parent::__set($name, $value);
        } else {
            Analog::log(
                '[EventsList] Setting property `' . $name . '`',
                Analog::DEBUG
            );

            switch ($name) {
                case 'start_date_filter':
                case 'end_date_filter':
                    try {
                        if ($value !== '') {
                            $y = \DateTime::createFromFormat(__("Y"), $value);
                            if ($y !== false) {
                                $month = 1;
                                $day = 1;
                                if ($name === 'end_date_filter') {
                                    $month = 12;
                                    $day = 31;
                                }
                                $y->setDate(
                                    (int)$y->format('Y'),
                                    $month,
                                    $day
                                );
                                $this->$name = $y->format('Y-m-d');
                            }

                            $ym = \DateTime::createFromFormat(__("Y-m"), $value);
                            if ($y === false && $ym  !== false) {
                                $day = 1;
                                if ($name === 'end_date_filter') {
                                    $day = $ym->format('t');
                                }
                                $ym->setDate(
                                    (int)$ym->format('Y'),
                                    (int)$ym->format('m'),
                                    $day
                                );
                                $this->$name = $ym->format('Y-m-d');
                            }

                            $d = \DateTime::createFromFormat(__("Y-m-d"), $value);
                            if ($y === false && $ym  === false && $d !== false) {
                                $this->$name = $d->format('Y-m-d');
                            }

                            if ($y === false && $ym === false && $d === false) {
                                $formats = array(
                                    __("Y"),
                                    __("Y-m"),
                                    __("Y-m-d"),
                                );

                                $field = null;
                                if ($name === 'start_date_filter') {
                                    $field = _T("start date filter");
                                }
                                if ($name === 'end_date_filter') {
                                    $field = _T("end date filter");
                                }

                                throw new \Exception(
                                    sprintf(
                                        //TRANS: %1$s is field label, %2$s is list of known date formats
                                        _T('Unknown date format for %1$s.<br/>Know formats are: %2$s'),
                                        $field,
                                        implode(', ', $formats)
                                    )
                                );
                            }
                        } else {
                            $this->$name = null;
                        }
                    } catch (\Exception $e) {
                        Analog::log(
                            'Wrong date format. field: ' . $name .
                            ', value: ' . $value . ', expected fmt: ' .
                            __("Y-m-d") . ' | ' . $e->getMessage(),
                            Analog::INFO
                        );
                        throw $e;
                    }
                    break;
                default:
                    $this->$name = $value;
                    break;
            }
        }
    }
}
