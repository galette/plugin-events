<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Events routes
 *
 * PHP version 5
 *
 * Copyright © 2018-2021 The Galette Team
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
 * @copyright 2018-2021 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 */

use Galette\Repository\Groups;
use GaletteEvents\Filters\BookingsList;
use GaletteEvents\Filters\ActivitiesList;
use GaletteEvents\Event;
use GaletteEvents\Booking;
use GaletteEvents\Activity;
use GaletteEvents\Repository\Bookings;
use GaletteEvents\Repository\Activities;
use Galette\Repository\Members;
use Galette\Filters\MembersList;
use Galette\Entity\Adherent;
use GaletteEvents\Controllers\Crud\EventsController;
use GaletteEvents\Controllers\Crud\ActivitiesController;
use GaletteEvents\Controllers\Crud\BookingsController;

//Constants and classes from plugin
require_once $module['root'] . '/_config.inc.php';

$this->get(
    '/events[/{option:page|order}/{value:\d+}]',
    [EventsController::class, 'list']
)->setName('events_events')->add($authenticate);

//events list filtering
$this->post(
    '/events/filter',
    [EventsController::class, 'filter']
)->setName('filter-eventslist')->add($authenticate);

$this->get(
    '/event/add',
    [EventsController::class, 'add']
)->setName(
    'events_event_add'
)->add($authenticate);

$this->get(
    '/event/edit/{id:\d+}',
    [EventsController::class, 'edit']
)->setName(
    'events_event_edit'
)->add($authenticate);

$this->post(
    '/event/add',
    [EventsController::class, 'doAdd']
)->setName('events_storeevent_add')->add($authenticate);

$this->post(
    '/event/edit/{id:\d}',
    [EventsController::class, 'doEdit']
)->setName('events_storeevent_edit')->add($authenticate);

$this->get(
    '/event/remove/{id:\d+}',
    [EventsController::class, 'confirmDelete']
)->setName('events_remove_event')->add($authenticate);

$this->post(
    '/event/remove[/{id:\d+}]',
    [EventsController::class, 'delete']
)->setName('events_do_remove_event')->add($authenticate);

$this->get(
    '/bookings/{event:guess|all|\d+}[/{option:page|order|clear_filter}/{value:\d+}]',
    [BookingsController::class, 'listBookings']
)->setName('events_bookings');

//bookings list filtering
$this->post(
    '/bookings/filter/{event:guess|all|\d+}',
    [BookingsController::class, 'filterBookings']
)->setName('filter-bookingslist')->add($authenticate);

$this->get(
    '/booking/add',
    [BookingsController::class, 'add']
)->setName('events_booking_add')->add($authenticate);

$this->get(
    '/booking/edit/{id:\d+}',
    [BookingsController::class, 'edit']
)->setName('events_booking_edit')->add($authenticate);

$this->post(
    '/booking/add',
    [BookingsController::class, 'doAdd']
)->setName('events_storebooking_add')->add($authenticate);

$this->post(
    '/booking/edit/{id:\d+}',
    [BookingsController::class, 'doEdit']
)->setName('events_storebooking_edit')->add($authenticate);

$this->get(
    '/booking/remove/{id:\d+}',
    [BookingsController::class, 'confirmDelete']
)->setName('events_remove_booking')->add($authenticate);

$this->post(
    '/booking/remove[/{id:\d+}]',
    [BookingsController::class, 'delete']
)->setName('events_do_remove_booking')->add($authenticate);

//booking CSV export
$this->map(
    ['GET', 'POST'],
    '/events/{id:\d+}/export/bookings',
    [GaletteEvents\Controllers\CsvController::class, 'bookingsExport']
)->setName('event_bookings_export')->add($authenticate);

$this->post(
    '/events/export/bookings',
    [GaletteEvents\Controllers\CsvController::class, 'bookingsExport']
)->setName('events_bookings_export')->add($authenticate);

//Batch actions on bookings list
$this->post(
    '/bookings/batch',
    [BookingsController::class, 'handleBatch']
)->setName('batch-eventslist')->add($authenticate);

$this->get(
    '/activities[/{option:page|order}/{value:\d+}]',
    [ActivitiesController::class, 'list']
)->setName('events_activities')->add($authenticate);

$this->get(
    '/activity/add',
    [ActivitiesController::class, 'add']
)->setName(
    'events_activity_add'
)->add($authenticate);

$this->get(
    '/activity/edit/{id:\d+}',
    [ActivitiesController::class, 'edit']
)->setName(
    'events_activity_edit'
)->add($authenticate);

$this->post(
    '/activity/add',
    [ActivitiesController::class, 'doAdd']
)->setName('events_storeactivity_add');

$this->post(
    '/activity/store',
    [ActivitiesController::class, 'doEdit']
)->setName('events_storeactivity_edit')->add($authenticate);

$this->get(
    '/activity/remove/{id:\d+}',
    [ActivitiesController::class, 'confirmDelete']
)->setName('events_remove_activity')->add($authenticate);

$this->post(
    '/activity/remove[/{id:\d+}]',
    [ActivitiesController::class, 'delete']
)->setName('events_do_remove_activity')->add($authenticate);

$this->get(
    '/events/calendar[/{option:page|order}/{value:\d+}]',
    [EventsController::class, 'calendar']
)->setName('events_calendar')->add($authenticate);

$this->get(
    '/ajax/events/calendar',
    [EventsController::class, 'ajaxCalendar']
)->setName('ajax-events_calendar')->add($authenticate);
