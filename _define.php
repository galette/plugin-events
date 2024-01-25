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

$this->register(
    'Galette Events',       //Name
    'Events management',    //Short description
    'Johan Cwiklinski',     //Author
    '2.0.0',                //Version
    '1.1.0',                //Galette compatible version
    'events',               //routing name and translation domain
    '2023-12-07',           //Release date
    [   //Permissions needed
        'events_events'             => 'member',
        'events_bookings'           => 'member',
        'filter-eventslist'         => 'member',
        'events_event_add'          => 'groupmanager',
        'events_event_edit'         => 'groupmanager',
        'events_storeevent_add'     => 'groupmanager',
        'events_storeevent_edit'    => 'groupmanager',
        'events_remove_event'       => 'staff',
        'events_do_remove_event'    => 'staff',
        'events_booking_add'        => 'member',
        'events_booking_edit'       => 'member',
        'events_storebooking_add'   => 'member',
        'events_storebooking_edit'  => 'member',
        'events_remove_booking'     => 'staff',
        'events_do_remove_booking'  => 'staff',
        'filter-bookingslist'       => 'member',
        'batch-eventslist'          => 'groupmanager',
        'events_activities'         => 'staff',
        'filter-activitieslist'     => 'staff',
        'events_activity_add'       => 'staff',
        'events_activity_edit'      => 'staff',
        'events_storeactivity_add'  => 'staff',
        'events_storeactivity_edit' => 'staff',
        'events_remove_activity'    => 'staff',
        'events_do_remove_activity' => 'staff',
        'event_bookings_export'     => 'groupmanager',
        'events_bookings_export'    => 'groupmanager',
        'events_calendar'           => 'member',
        'ajax-events_calendar'      => 'member'
    ]
);
