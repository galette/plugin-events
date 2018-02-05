<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Bookings lists filters and paginator
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
 * @category  Filters
 * @package   GaletteEvents
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2018 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */

namespace GaletteEvents\Filters;

use Analog\Analog;
use Galette\Core\Pagination;
use GaletteEvents\Repository\Bookings;

/**
 * Bookings lists filters and paginator
 *
 * @name      BookingsList
 * @category  Filters
 * @package   GaletteEvents
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2018 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */

class BookingsList extends Pagination
{
    //filters
    private $event_filter;
    /*private $name_filter = null;
    private $start_date_filter = null;
    private $end_date_filter = null;
    private $group_filter = 0;
    private $meal_filter = null;
    private $lodging_filter = null;
    private $open_filter = null;*/

    protected $list_fields = array(
        'event_filter'
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
     * @return string field name
     */
    protected function getDefaultOrder()
    {
        return Bookings::ORDERBY_DATE;
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
        $this->event_filter = null;
    }

    /**
     * Global getter method
     *
     * @param string $name name of the property we want to retrive
     *
     * @return object the called property
     */
    public function __get($name)
    {
        Analog::log(
            '[BookingsList] Getting property `' . $name . '`',
            Analog::DEBUG
        );

        if (in_array($name, $this->pagination_fields)) {
            return parent::__get($name);
        } else {
            if (in_array($name, $this->list_fields)) {
                return $this->$name;
            } else {
                Analog::log(
                    '[BookingsList] Unable to get proprety `' .$name . '`',
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
                '[BookingsList] Setting property `' . $name . '`',
                Analog::DEBUG
            );
            $this->$name = $value;
        }
    }

    /**
     * Build href
     * Overrided to add "event" parameter
     *
     * @param int $page Page
     *
     * @return string
     */
    protected function getHref($page)
    {
        $args = [
            'option'    => 'page',
            'value'     => $page,
            'event'     => 'all'
        ];

        if ($this->view->getTemplateVars('cur_subroute')) {
            $args['type'] = $this->view->getTemplateVars('cur_subroute');
        }

        $href = $this->router->pathFor(
            $this->view->getTemplateVars('cur_route'),
            $args
        );
        return $href;
    }
}
