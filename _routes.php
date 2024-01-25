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

$app->get(
    '/events[/{option:page|order}/{value:\d+}]',
    [EventsController::class, 'list']
)->setName('events_events')->add($authenticate);

//events list filtering
$app->post(
    '/events/filter',
    [EventsController::class, 'filter']
)->setName('filter-eventslist')->add($authenticate);

$app->get(
    '/event/add',
    [EventsController::class, 'add']
)->setName(
    'events_event_add'
)->add($authenticate);

$app->get(
    '/event/edit/{id:\d+}',
    [EventsController::class, 'edit']
)->setName(
    'events_event_edit'
)->add($authenticate);

$app->post(
    '/event/add',
    [EventsController::class, 'doAdd']
)->setName('events_storeevent_add')->add($authenticate);

$app->post(
    '/event/edit/{id:\d}',
    [EventsController::class, 'doEdit']
)->setName('events_storeevent_edit')->add($authenticate);

$app->get(
    '/event/remove/{id:\d+}',
    [EventsController::class, 'confirmDelete']
)->setName('events_remove_event')->add($authenticate);

$app->post(
    '/event/remove[/{id:\d+}]',
    [EventsController::class, 'delete']
)->setName('events_do_remove_event')->add($authenticate);

$app->get(
    '/bookings/{event:guess|all|\d+}[/{option:page|order|clear_filter}/{value:\d+}]',
    [BookingsController::class, 'listBookings']
)->setName('events_bookings');

//bookings list filtering
$app->post(
    '/bookings/filter/{event:guess|all|\d+}',
    [BookingsController::class, 'filterBookings']
)->setName('filter-bookingslist')->add($authenticate);

$app->get(
    '/booking/add[/{id_adh:\d+}]',
    [BookingsController::class, 'add']
)->setName('events_booking_add')->add($authenticate);

$app->get(
    '/booking/edit/{id:\d+}',
    [BookingsController::class, 'edit']
)->setName('events_booking_edit')->add($authenticate);

$app->post(
    '/booking/add',
    [BookingsController::class, 'doAdd']
)->setName('events_storebooking_add')->add($authenticate);

$app->post(
    '/booking/edit/{id:\d+}',
    [BookingsController::class, 'doEdit']
)->setName('events_storebooking_edit')->add($authenticate);

$app->get(
    '/booking/remove/{id:\d+}',
    [BookingsController::class, 'confirmDelete']
)->setName('events_remove_booking')->add($authenticate);

$app->post(
    '/booking/remove[/{id:\d+}]',
    [BookingsController::class, 'delete']
)->setName('events_do_remove_booking')->add($authenticate);

//booking CSV export
$app->map(
    ['GET', 'POST'],
    '/events/{id:\d+}/export/bookings',
    [GaletteEvents\Controllers\CsvController::class, 'bookingsExport']
)->setName('event_bookings_export')->add($authenticate);

$app->post(
    '/events/export/bookings',
    [GaletteEvents\Controllers\CsvController::class, 'bookingsExport']
)->setName('events_bookings_export')->add($authenticate);

//Batch actions on bookings list
$app->post(
    '/bookings/batch',
    [BookingsController::class, 'handleBatch']
)->setName('batch-eventslist')->add($authenticate);

$app->get(
    '/activities[/{option:page|order}/{value:\d+}]',
    [ActivitiesController::class, 'list']
)->setName('events_activities')->add($authenticate);

$app->post(
    '/activities/filter',
    [ActivitiesController::class, 'filter']
)->setName('filter-activitieslist')->add($authenticate);

$app->get(
    '/activity/add',
    [ActivitiesController::class, 'add']
)->setName(
    'events_activity_add'
)->add($authenticate);

$app->get(
    '/activity/edit/{id:\d+}',
    [ActivitiesController::class, 'edit']
)->setName(
    'events_activity_edit'
)->add($authenticate);

$app->post(
    '/activity/add',
    [ActivitiesController::class, 'doAdd']
)->setName('events_storeactivity_add');

$app->post(
    '/activity/store',
    [ActivitiesController::class, 'doEdit']
)->setName('events_storeactivity_edit')->add($authenticate);

$app->get(
    '/activity/remove/{id:\d+}',
    [ActivitiesController::class, 'confirmDelete']
)->setName('events_remove_activity')->add($authenticate);

$app->post(
    '/activity/remove[/{id:\d+}]',
    [ActivitiesController::class, 'delete']
)->setName('events_do_remove_activity')->add($authenticate);

$app->get(
    '/events/calendar[/{option:page|order}/{value:\d+}]',
    [EventsController::class, 'calendar']
)->setName('events_calendar')->add($authenticate);

$app->get(
    '/ajax/events/calendar',
    [EventsController::class, 'ajaxCalendar']
)->setName('ajax-events_calendar')->add($authenticate);
