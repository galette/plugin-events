<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Activities lists filters and paginator
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
 * @category  Filters
 * @package   GaletteEvents
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2018-2023 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */

namespace GaletteEvents\Filters;

use Analog\Analog;
use Galette\Core\Pagination;
use GaletteEvents\Repository\Activities;

/**
 * Events lists filters and paginator
 *
 * @name      ActivitiesList
 * @category  Filters
 * @package   GaletteEvents
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2018-2023 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 *
 * @property string $query
 */

class ActivitiesList extends Pagination
{
    //filters
    private $name_filter = null;
    private $active_filter = null;
    private string $query;

    protected $list_fields = array(
        'name_filter',
        'active_filter'
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
    protected function getDefaultOrder()
    {
        return Activities::ORDERBY_DATE;
    }

    /**
     * Return the default direction for ordering
     *
     * @return string ASC or DESC
     */
    protected function getDefaultDirection()
    {
        return self::ORDER_DESC;
    }

    /**
     * Reinit default parameters
     *
     * @return void
     */
    public function reinit()
    {
        parent::reinit();
        $this->name_filter = null;
        $this->active_filter = null;
    }

    /**
     * Global getter method
     *
     * @param string $name name of the property we want to retrieve
     *
     * @return mixed the called property
     */
    public function __get($name)
    {
        Analog::log(
            '[ActivitiesList] Getting property `' . $name . '`',
            Analog::DEBUG
        );

        if (in_array($name, $this->pagination_fields)) {
            return parent::__get($name);
        } else {
            if (in_array($name, $this->list_fields)) {
                return $this->$name;
            } else {
                Analog::log(
                    '[ActivitiesList] Unable to get proprety `' . $name . '`',
                    Analog::WARNING
                );
            }
        }
    }

    /**
     * Global setter method
     *
     * @param string $name  name of the property we want to assign a value to
     * @param object $value a relevant value for the property
     *
     * @return void
     */
    public function __set($name, $value)
    {
        if (in_array($name, $this->pagination_fields)) {
            parent::__set($name, $value);
        } else {
            Analog::log(
                '[ActivitiesList] Setting property `' . $name . '`',
                Analog::DEBUG
            );

            $this->$name = $value;
        }
    }
}
