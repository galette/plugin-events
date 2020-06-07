<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Configuration file for Paypal plugin
 *
 * PHP version 5
 *
 * Copyright © 2017-2018 The Galette Team
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
 * @category  Plugins
 * @package   GaletteEvents
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2017-2018 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 */

$this->register(
    'Galette Events',       //Name
    'Events management',    //Short description
    'Johan Cwiklinski',     //Author
    '1.3.0',                //Version
    '0.9.4',                //Galette compatible version
    'events',               //routing name and translation domain
    '2020-06-07',           //Release date
    [   //Permissions needed
        'events_events'             => 'member',
        'events_bookings'           => 'member',
        'filter-eventslist'         => 'member',
        'events_event'              => 'groupmanager',
        'events_storeevent'         => 'groupmanager',
        'events_remove_event'       => 'staff',
        'events_do_remove_event'    => 'staff',
        'events_booking'            => 'member',
        'events_storebooking'       => 'member',
        'events_remove_booking'     => 'staff',
        'events_do_remove_booking'  => 'staff',
        'filter-bookingslist'       => 'member',
        'batch-eventslist'          => 'groupmanager',
        'events_activities'         => 'staff',
        'events_activity'           => 'staff',
        'events_storeactivity'      => 'staff',
        'events_remove_activity'    => 'staff',
        'events_do_remove_activity' => 'staff',
        'events_booking_export'     => 'staff',
        'events_calendar'           => 'member',
        'ajax-events_calendar'      => 'member'
    ]
);
