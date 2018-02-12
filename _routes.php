<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Events routes
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
 * @category  Plugins
 * @package   GalettePaypal
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2018 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 */

use Analog\Analog;
use Galette\Repository\Groups;
use GaletteEvents\Filters\EventsList;
use GaletteEvents\Filters\BookingsList;
use GaletteEvents\Filters\ActivitiesList;
use GaletteEvents\Event;
use GaletteEvents\Booking;
use GaletteEvents\Activity;
use GaletteEvents\Repository\Events;
use GaletteEvents\Repository\Bookings;
use GaletteEvents\Repository\Activities;
use Galette\Repository\Members;
use Galette\Filters\MembersList;

//Constants and classes from plugin
require_once $module['root'] . '/_config.inc.php';

$this->get(
    __('/events', 'events_routes') . '[/{option:' . __('page', 'routes') . '|' .
    __('order', 'routes') . '}/{value:\d+}]',
    function ($request, $response, $args) use ($module, $module_id) {
        $option = null;
        if (isset($args['option'])) {
            $option = $args['option'];
        }
        $value = null;
        if (isset($args['value'])) {
            $value = $args['value'];
        }

        if (isset($this->session->filter_events)) {
            $filters = $this->session->filter_events;
        } else {
            $filters = new EventsList();
        }

        if ($option !== null) {
            switch ($option) {
                case __('page', 'routes'):
                    $filters->current_page = (int)$value;
                    break;
                case __('order', 'routes'):
                    $filters->orderby = $value;
                    break;
            }
        }

        $events = new Events($this->zdb, $this->login, $filters);

        //assign pagination variables to the template and add pagination links
        $filters->setSmartyPagination($this->router, $this->view->getSmarty(), false);

        $this->session->filter_events = $filters;

        // display page
        $this->view->render(
            $response,
            'file:[' . $module['route'] . ']events.tpl',
            array(
                'page_title'            => _T("Events management", "events"),
                'require_dialog'        => true,
                'events'                => $events->getList(),
                'nb_events'             => $events->getCount(),
                'filters'               => $filters
            )
        );
        return $response;
    }
)->setName('events_events')->add($authenticate);

//events list filtering
$this->post(
    __('/events', 'events_routes') . __('/filter', 'routes'),
    function ($request, $response) {
        $post = $request->getParsedBody();
        if (isset($this->session->filter_events)) {
            $filters = $this->session->filter_events;
        } else {
            $filters = new EventsList();
        }

        //reintialize filters
        if (isset($post['clear_filter'])) {
            $filters->reinit();
        } else {
            //number of rows to show
            if (isset($post['nbshow'])) {
                $filters->show = $post['nbshow'];
            }
        }

        $this->session->filter_events = $filters;

        return $response
            ->withStatus(301)
            ->withHeader('Location', $this->router->pathFor('events_events'));
    }
)->setName('filter-eventslist')->add($authenticate);

$this->get(
    __('/event', 'events_routes') . '/{action:' . __('edit', 'routes') . '|' . __('add', 'routes') . '}[/{id:\d+}]',
    function ($request, $response, $args) use ($module, $module_id) {
        $action = $args['action'];
        $id = null;
        if (isset($args['id'])) {
            $id = $args['id'];
        }

        if ($action === __('edit', 'routes') && $id === null) {
            throw new \RuntimeException(
                _T("Event ID cannot ben null calling edit route!", "events")
            );
        } elseif ($action === __('add', 'routes') && $id !== null) {
             return $response
                ->withStatus(301)
                ->withHeader('Location', $this->router->pathFor('events_event', ['action' => __('add', 'routes')]));
        }
        $route_params = ['action' => $args['action']];

        if ($this->session->event !== null) {
            $event = $this->session->event;
            $this->session->event = null;
        } else {
            $event = new Event($this->zdb, $this->login);
        }

        if ($id !== null && $event->getId() != $id) {
            $event->load($id);
        }

        // template variable declaration
        $title = _T("Event");
        if ($event->getId() != '') {
            $title .= ' (' . _T("modification") . ')';
        } else {
            $title .= ' (' . _T("creation") . ')';
        }

        //Groups
        $groups = new Groups($this->zdb, $this->login);
        $groups_list = $groups->getList();

        // display page
        $this->view->render(
            $response,
            'file:[' . $module['route'] . ']event.tpl',
            array_merge(
                $route_params,
                array(
                    'autocomplete'      => true,
                    'page_title'        => $title,
                    'event'             => $event,
                    'require_calendar'  => true,
                    // pseudo random int
                    'time'              => time(),
                    'groups'            => $groups_list,
                )
            )
        );
        return $response;
    }
)->setName(
    'events_event'
)->add($authenticate);

$this->post(
    __('/event', 'events_routes') . __('/store', 'routes'),
    function ($request, $response, $args) {
        $post = $request->getParsedBody();
        $event = new Event($this->zdb, $this->login);
        if (isset($post['id']) && !empty($post['id'])) {
            $event->load((int)$post['id']);
        }

        $success_detected = [];
        $warning_detected = [];
        $error_detected = [];
        $goto_list = true;

        // Validation
        $valid = $event->check($post);
        if ($valid !== true) {
            $error_detected = array_merge($error_detected, $valid);
        }

        if (count($error_detected) == 0) {
            //all goes well, we can proceed
            $new = false;
            if ($event->getId() == '') {
                $new = true;
            }

            if (isset($post['add_activity'])) {
                $this->session->event = $event;
                $success_detected[] = _T("Activity has been attached to event.", "events");
                $goto_list = false;
            } elseif (isset($post['save'])) {
                $store = $event->store();
                if ($store === true) {
                    //member has been stored :)
                    if ($new) {
                        $success_detected[] = _T("New event has been successfully added.", "events");
                    } else {
                        $success_detected[] = _T("Event has been modified.", "events");
                    }
                } else {
                    //something went wrong :'(
                    $error_detected[] = _T("An error occured while storing the event.", "events");
                }
            } else {
                $this->session->event = $event;
                $error_detected = [];
                $goto_list = false;
            }
        }

        if (count($error_detected) > 0) {
            foreach ($error_detected as $error) {
                $this->flash->addMessage(
                    'error_detected',
                    $error
                );
            }
        }

        if (count($warning_detected) > 0) {
            foreach ($warning_detected as $warning) {
                $this->flash->addMessage(
                    'warning_detected',
                    $warning
                );
            }
        }
        if (count($success_detected) > 0) {
            foreach ($success_detected as $success) {
                $this->flash->addMessage(
                    'success_detected',
                    $success
                );
            }
        }

        if (count($error_detected) == 0 && $goto_list) {
            $redirect_url = $this->router->pathFor('events_events');
        } else {
            //store entity in session
            $this->session->event = $event;

            if ($event->getId()) {
                $rparams = [
                    'id'        => $event->getId(),
                    'action'    => __('edit', 'routes')
                ];
            } else {
                $rparams = ['action' => __('add', 'routes')];
            }
            $redirect_url = $this->router->pathFor(
                'events_event',
                $rparams
            );
        }

        return $response
            ->withStatus(301)
            ->withHeader('Location', $redirect_url);
    }
)->setName('events_storeevent')->add($authenticate);

$this->get(
    __('/event', 'events_routes') . __('/remove', 'routes') . '/{id:\d+}',
    function ($request, $response, $args) {
        $event = new Event($this->zdb, $this->login, (int)$args['id']);

        $data = [
            'id'            => $args['id'],
            'redirect_uri'  => $this->router->pathFor('events_events')
        ];

        // display page
        $this->view->render(
            $response,
            'confirm_removal.tpl',
            array(
                'type'          => _T("Event", "events"),
                'mode'          => $request->isXhr() ? 'ajax' : '',
                'page_title'    => sprintf(
                    _T('Remove event %1$s'),
                    $event->getName()
                ),
                'form_url'      => $this->router->pathFor(
                    'events_do_remove_event',
                    ['id' => $event->getId()]
                ),
                'cancel_uri'    => $this->router->pathFor('events_events'),
                'data'          => $data
            )
        );
        return $response;
    }
)->setName('events_remove_event')->add($authenticate);

$this->post(
    __('/event', 'events_routes') . __('/remove', 'routes') . '[/{id:\d+}]',
    function ($request, $response) {
        $post = $request->getParsedBody();
        $ajax = isset($post['ajax']) && $post['ajax'] === 'true';
        $success = false;

        $uri = isset($post['redirect_uri']) ?
            $post['redirect_uri'] :
            $this->router->pathFor('slash');

        if (!isset($post['confirm'])) {
            $this->flash->addMessage(
                'error_detected',
                _T("Removal has not been confirmed!")
            );
        } else {
            $event = new Event($this->zdb, $this->login, (int)$post['id']);
            $del = $event->remove();

            if ($del !== true) {
                $error_detected = str_replace(
                    '%name',
                    $event->getName(),
                    _T("An error occured trying to remove event %name :/", "events")
                );

                $this->flash->addMessage(
                    'error_detected',
                    $error_detected
                );
            } else {
                $success_detected = str_replace(
                    '%name',
                    $event->getName(),
                    _T("Event %name has been successfully deleted.", "events")
                );

                $this->flash->addMessage(
                    'success_detected',
                    $success_detected
                );

                $success = true;
            }
        }

        if (!$ajax) {
            return $response
                ->withStatus(301)
                ->withHeader('Location', $uri);
        } else {
            return $response->withJson(
                [
                    'success'   => $success
                ]
            );
        }
    }
)->setName('events_do_remove_event')->add($authenticate);

$this->get(
    __('/bookings', 'events_routes') . '/{event:guess|'. __('all', 'events_routes') . '|\d+}' .
    '[/{option:' . __('page', 'routes') . '|' . __('order', 'routes') . '}/{value:\d+}]',
    function ($request, $response, $args) use ($module, $module_id) {
        $option = null;
        if (isset($args['option'])) {
            $option = $args['option'];
        }
        $value = null;
        if (isset($args['value'])) {
            $value = $args['value'];
        }

        if (isset($this->session->filter_bookings)) {
            $filters = $this->session->filter_bookings;
        } else {
            $filters = new BookingsList();
        }

        if ($args['event'] == 'guess') {
            $args['event'] = $filters->event_filter;
        } else {
            $filters->event_filter = $args['event'];
        }

        if ($option !== null) {
            switch ($option) {
                case __('page', 'routes'):
                    $filters->current_page = (int)$value;
                    break;
                case __('order', 'routes'):
                    $filters->orderby = $value;
                    break;
            }
        }

        $event = null;
        if ($args['event'] !== __('all', 'events_routes')) {
            $filters->event_filter = (int)$args['event'];
            $event = new Event($this->zdb, $this->login, (int)$args['event']);
        }

        $bookings = new Bookings($this->zdb, $this->login, $filters);

        //assign pagination variables to the template and add pagination links
        $filters->setSmartyPagination($this->router, $this->view->getSmarty(), false);

        $this->session->filter_bookings = $filters;

        $list = $bookings->getList();
        $count = $bookings->getCount();
        // display page
        $this->view->render(
            $response,
            'file:[' . $module['route'] . ']bookings.tpl',
            [
                'page_title'        => _T("Bookings management", "events"),
                'bookings'          => $bookings,
                'bookings_list'     => $list,
                'nb_bookings'       => $count,
                'event'             => $event,
                'eventid'           => $filters->event_filter,
                'require_dialog'    => true,
                'filters'           => $filters
            ]
        );
        return $response;
    }
)->setName('events_bookings');

//bookings list filtering
$this->post(
    __('/bookings', 'events_routes') . __('/filter', 'routes'),
    function ($request, $response) {
        $post = $request->getParsedBody();
        if (isset($this->session->filter_bookings)) {
            $filters = $this->session->filter_bookings;
        } else {
            $filters = new BookingsList();
        }

        //reintialize filters
        if (isset($post['clear_filter'])) {
            $filters->reinit();
        } else {
            //number of rows to show
            if (isset($post['nbshow'])) {
                $filters->show = $post['nbshow'];
            }

            if (isset($post['paid_filter'])) {
                if (is_numeric($post['paid_filter'])) {
                    $filters->paid_filter = $post['paid_filter'];
                }
            }

            if (isset($post['payment_type_filter'])) {
                if (is_numeric($post['payment_type_filter'])) {
                    $filters->payment_type_filter = $post['payment_type_filter'];
                }
            }
        }

        $this->session->filter_bookings = $filters;

        return $response
            ->withStatus(301)
            ->withHeader(
                'Location',
                $this->router->pathFor('events_bookings', ['event' => $filters->event_filter])
            );
    }
)->setName('filter-bookingslist')->add($authenticate);

$this->get(
    __('/booking', 'events_routes') . '/{action:' . __('edit', 'routes') . '|' . __('add', 'routes') .
    '}[/{id:\d+}]',
    function ($request, $response, $args) use ($module, $module_id) {
        $action = $args['action'];
        $get = $request->getQueryParams();

        $id = null;
        if (isset($args['id'])) {
            $id = $args['id'];
        }

        if ($action === __('edit', 'routes') && $id === null) {
            throw new \RuntimeException(
                _T("Booking ID cannot ben null calling edit route!", "events")
            );
        } elseif ($action === __('add', 'routes') && $id !== null) {
             return $response
                ->withStatus(301)
                ->withHeader('Location', $this->router->pathFor('events_bookings', ['action' => __('add', 'routes')]));
        }
        $route_params = ['action' => $args['action']];

        if ($this->session->booking !== null) {
            $booking = $this->session->booking;
            $this->session->booking = null;
        } else {
            $booking = new Booking($this->zdb, $this->login);
        }

        if ($id !== null && $booking->getId() != $id) {
            $booking->load($id);
        }

        // template variable declaration
        $title = _T("Booking", "events");
        if ($booking->getId() != '') {
            $title .= ' (' . _T("modification") . ')';
        } else {
            $title .= ' (' . _T("creation") . ')';
        }

        //Events
        $events = new Events($this->zdb, $this->login);
        if ($action === __('add', 'routes')) {
            if (isset($get['event'])) {
                $booking->setEvent((int)$get['event']);
            }
            if (!$this->login->isSuperAdmin()) {
                $booking->setMember($this->login->id);
            }
        }

        // display page
        $this->view->render(
            $response,
            'file:[' . $module['route'] . ']booking.tpl',
            array_merge(
                $route_params,
                array(
                    'autocomplete'      => true,
                    'page_title'        => $title,
                    'booking'           => $booking,
                    'events'            => $events->getList(),
                    'require_dialog'    => true,
                    'require_calendar'  => true,
                    // pseudo random int
                    'time'              => time()
                )
            )
        );
        return $response;
    }
)->setName('events_booking')->add($authenticate);

$this->post(
    __('/booking', 'events_routes') . __('/store', 'routes'),
    function ($request, $response, $args) {
        $post = $request->getParsedBody();
        $booking = new Booking($this->zdb, $this->login);
        if (isset($post['id']) && !empty($post['id'])) {
            $booking->load((int)$post['id']);
        }

        $success_detected = [];
        $warning_detected = [];
        $error_detected = [];

        // Validation
        $valid = $booking->check($post);
        if ($valid !== true) {
            $error_detected = array_merge($error_detected, $valid);
        }

        if (count($error_detected) == 0) {
            //all goes well, we can proceed

            $new = false;
            if ($booking->getId() == '') {
                $new = true;
            }
            $store = $booking->store();
            if ($store === true) {
                //member has been stored :)
                if ($new) {
                    $success_detected[] = _T("New booking has been successfully added.", "events");
                } else {
                    $success_detected[] = _T("Booking has been modified.", "events");
                }
            } elseif ($store === false) {
                //something went wrong :'(
                $error_detected[] = _T("An error occured while storing the booking.", "events");
            } else {
                $error_detected[] = $store;
            }
        }

        if (count($error_detected) > 0) {
            foreach ($error_detected as $error) {
                $this->flash->addMessage(
                    'error_detected',
                    $error
                );
            }
        }

        if (count($warning_detected) > 0) {
            foreach ($warning_detected as $warning) {
                $this->flash->addMessage(
                    'warning_detected',
                    $warning
                );
            }
        }
        if (count($success_detected) > 0) {
            foreach ($success_detected as $success) {
                $this->flash->addMessage(
                    'success_detected',
                    $success
                );
            }
        }

        if (count($error_detected) == 0) {
            $redirect_url = $this->router->pathFor(
                'events_bookings',
                ['event' => $booking->getEventId()]
            );
        } else {
            //store entity in session
            $this->session->booking = $booking;

            if ($booking->getId()) {
                $rparams = [
                    'id'        => $booking->getId(),
                    'action'    => __('edit', 'routes')
                ];
            } else {
                $rparams = ['action' => __('add', 'routes')];
            }
            $redirect_url = $this->router->pathFor(
                'events_booking',
                $rparams
            );
        }

        return $response
            ->withStatus(301)
            ->withHeader('Location', $redirect_url);
    }
)->setName('events_storebooking')->add($authenticate);

$this->get(
    __('/booking', 'events_routes') . __('/remove', 'routes') . '/{id:\d+}',
    function ($request, $response, $args) {
        $booking = new Booking($this->zdb, $this->login, (int)$args['id']);

        $data = [
            'id'            => $args['id'],
            'redirect_uri'  => $this->router->pathFor('events_bookings', ['event' => $booking->getEventId()])
        ];

        // display page
        $this->view->render(
            $response,
            'confirm_removal.tpl',
            array(
                'type'          => _T("Booking", "events"),
                'mode'          => $request->isXhr() ? 'ajax' : '',
                'page_title'    => _T('Remove booking'),
                'form_url'      => $this->router->pathFor(
                    'events_do_remove_booking',
                    ['id' => $booking->getId()]
                ),
                'cancel_uri'    => $this->router->pathFor('events_bookings', ['event' => $booking->getEventId()]),
                'data'          => $data
            )
        );
        return $response;
    }
)->setName('events_remove_booking')->add($authenticate);

$this->post(
    __('/booking', 'events_routes') . __('/remove', 'routes') . '[/{id:\d+}]',
    function ($request, $response) {
        $post = $request->getParsedBody();
        $ajax = isset($post['ajax']) && $post['ajax'] === 'true';
        $success = false;

        $uri = isset($post['redirect_uri']) ?
            $post['redirect_uri'] :
            $this->router->pathFor('slash');

        if (!isset($post['confirm'])) {
            $this->flash->addMessage(
                'error_detected',
                _T("Removal has not been confirmed!")
            );
        } else {
            $booking = new Booking($this->zdb, $this->login, (int)$post['id']);
            $del = $booking->remove();

            if ($del !== true) {
                $error_detected = _T("An error occured trying to remove booking :/", "events");

                $this->flash->addMessage(
                    'error_detected',
                    $error_detected
                );
            } else {
                $success_detected = _T("Booking has been successfully deleted.", "events");

                $this->flash->addMessage(
                    'success_detected',
                    $success_detected
                );

                $success = true;
            }
        }

        if (!$ajax) {
            return $response
                ->withStatus(301)
                ->withHeader('Location', $uri);
        } else {
            return $response->withJson(
                [
                    'success'   => $success
                ]
            );
        }
    }
)->setName('events_do_remove_booking')->add($authenticate);

//Batch actions on members list
$this->post(
    __('/bookings', 'events_routes') . __('/batch', 'routes'),
    function ($request, $response) {
        $post = $request->getParsedBody();

        if (isset($post['event_sel'])) {
            if (isset($this->session->filter_bookings)) {
                $filters = clone $this->session->filter_bookings;
            } else {
                $filters = new BookingsList();
            }

            //$this->session->filter_bookings = $filters;
            $filters->selected = $post['event_sel'];

            if (isset($post['mailing'])) {
                $bookings = new Bookings($this->zdb, $this->login, $filters);
                $members = [];
                foreach ($bookings->getList() as $booking) {
                    $members[] = $booking->getMemberId();
                }
                $mfilter = new MembersList();
                $mfilter->selected = $members;
                $this->session->filter_mailing = $mfilter;
                $this->session->redirect_mailing = $this->router->pathFor(
                    'events_bookings',
                    [
                        'event' => $filters->event_filter == null ?
                            __('all', 'events_routes') :
                            $filters->event_filter
                    ]
                );
                return $response
                    ->withStatus(301)
                    ->withHeader('Location', $this->router->pathFor('mailing') . '?new=new');
            }

            if (isset($post['csv'])) {
                return $response
                    ->withStatus(301)
                    ->withHeader('Location', $this->router->pathFor('csv-memberslist'));
            }
        } else {
            $this->flash->addMessage(
                'error_detected',
                _T("No booking was selected, please check at least one.", "events")
            );

            return $response
                ->withStatus(301)
                ->withHeader('Location', $this->router->pathFor('members'));
        }
    }
)->setName('batch-eventslist')->add($authenticate);

$this->get(
    __('/activities', 'events_routes') . '[/{option:' . __('page', 'routes') . '|' .
    __('order', 'routes') . '}/{value:\d+}]',
    function ($request, $response, $args) use ($module, $module_id) {
        $option = null;
        if (isset($args['option'])) {
            $option = $args['option'];
        }
        $value = null;
        if (isset($args['value'])) {
            $value = $args['value'];
        }

        if (isset($this->session->filter_activities)) {
            $filters = $this->session->filter_activities;
        } else {
            $filters = new ActivitiesList();
        }

        if ($option !== null) {
            switch ($option) {
                case __('page', 'routes'):
                    $filters->current_page = (int)$value;
                    break;
                case __('order', 'routes'):
                    $filters->orderby = $value;
                    break;
            }
        }

        $activities = new Activities($this->zdb, $this->login, $this->preferences, $filters);
        $list = $activities->getList();
        if (!count($list)) {
            $activities->installInit();
            $list = $activities->getList();
        }

        //assign pagination variables to the template and add pagination links
        $filters->setSmartyPagination($this->router, $this->view->getSmarty(), false);

        $this->session->filter_activities = $filters;

        // display page
        $this->view->render(
            $response,
            'file:[' . $module['route'] . ']activities.tpl',
            array(
                'page_title'            => _T("Activities management", "events"),
                'require_dialog'        => true,
                'activities'            => $list,
                'nb_activities'         => $activities->getCount(),
                'filters'               => $filters
            )
        );
        return $response;
    }
)->setName('events_activities')->add($authenticate);
$this->get(
    __('/activity', 'events_routes') . '/{action:' . __('edit', 'routes') . '|' . __('add', 'routes') . '}[/{id:\d+}]',
    function ($request, $response, $args) use ($module, $module_id) {
        $action = $args['action'];
        $id = null;
        if (isset($args['id'])) {
            $id = $args['id'];
        }

        if ($action === __('edit', 'routes') && $id === null) {
            throw new \RuntimeException(
                _T("Activity ID cannot ben null calling edit route!", "events")
            );
        } elseif ($action === __('add', 'routes') && $id !== null) {
             return $response
                ->withStatus(301)
                ->withHeader('Location', $this->router->pathFor('events_activity', ['action' => __('add', 'routes')]));
        }
        $route_params = ['action' => $args['action']];

        if ($this->session->activity !== null) {
            $event = $this->session->activity;
            $this->session->activity = null;
        } else {
            $activity = new Activity($this->zdb, $this->login);
        }

        if ($id !== null && $activity->getId() != $id) {
            $activity->load($id);
        }

        // template variable declaration
        $title = _T("Activity");
        if ($activity->getId() != '') {
            $title .= ' (' . _T("modification") . ')';
        } else {
            $title .= ' (' . _T("creation") . ')';
        }

        // display page
        $this->view->render(
            $response,
            'file:[' . $module['route'] . ']activity.tpl',
            array_merge(
                $route_params,
                array(
                    'autocomplete'  => true,
                    'page_title'    => $title,
                    'activity'      => $activity,
                    // pseudo random int
                    'time'          => time()
                )
            )
        );
        return $response;
    }
)->setName(
    'events_activity'
)->add($authenticate);

$this->post(
    __('/activity', 'events_routes') . __('/store', 'routes'),
    function ($request, $response, $args) {
        $post = $request->getParsedBody();
        $activity = new Activity($this->zdb, $this->login);
        if (isset($post['id']) && !empty($post['id'])) {
            $activity->load((int)$post['id']);
        }

        $success_detected = [];
        $warning_detected = [];
        $error_detected = [];

        // Validation
        $valid = $activity->check($post);
        if ($valid !== true) {
            $error_detected = array_merge($error_detected, $valid);
        }

        if (count($error_detected) == 0) {
            //all goes well, we can proceed

            $new = false;
            if ($activity->getId() == '') {
                $new = true;
            }
            $store = $activity->store();
            if ($store === true) {
                //member has been stored :)
                if ($new) {
                    $success_detected[] = _T("New activity has been successfully added.", "events");
                } else {
                    $success_detected[] = _T("Activity has been modified.", "events");
                }
            } else {
                //something went wrong :'(
                $error_detected[] = _T("An error occured while storing the activity.", "events");
            }
        }

        if (count($error_detected) > 0) {
            foreach ($error_detected as $error) {
                $this->flash->addMessage(
                    'error_detected',
                    $error
                );
            }
        }

        if (count($warning_detected) > 0) {
            foreach ($warning_detected as $warning) {
                $this->flash->addMessage(
                    'warning_detected',
                    $warning
                );
            }
        }
        if (count($success_detected) > 0) {
            foreach ($success_detected as $success) {
                $this->flash->addMessage(
                    'success_detected',
                    $success
                );
            }
        }

        if (count($error_detected) == 0) {
            $redirect_url = $this->router->pathFor('events_activities');
        } else {
            //store entity in session
            $this->session->activity = $activity;

            if ($activity->getId()) {
                $rparams = [
                    'id'        => $activity->getId(),
                    'action'    => __('edit', 'routes')
                ];
            } else {
                $rparams = ['action' => __('add', 'routes')];
            }
            $redirect_url = $this->router->pathFor(
                'events_activity',
                $rparams
            );
        }

        return $response
            ->withStatus(301)
            ->withHeader('Location', $redirect_url);
    }
)->setName('events_storeactivity')->add($authenticate);

$this->get(
    __('/activity', 'events_routes') . __('/remove', 'routes') . '/{id:\d+}',
    function ($request, $response, $args) {
        $activity = new Activity($this->zdb, $this->login, (int)$args['id']);

        $data = [
            'id'            => $args['id'],
            'redirect_uri'  => $this->router->pathFor('events_activities')
        ];

        // display page
        $this->view->render(
            $response,
            'confirm_removal.tpl',
            array(
                'type'          => _T("Activity", "events"),
                'mode'          => $request->isXhr() ? 'ajax' : '',
                'page_title'    => sprintf(
                    _T('Remove activity %1$s'),
                    $activity->getName()
                ),
                'form_url'      => $this->router->pathFor(
                    'events_do_remove_activity',
                    ['id' => $activity->getId()]
                ),
                'cancel_uri'    => $this->router->pathFor('events_activities'),
                'data'          => $data
            )
        );
        return $response;
    }
)->setName('events_remove_activity')->add($authenticate);

$this->post(
    __('/activity', 'events_routes') . __('/remove', 'routes') . '[/{id:\d+}]',
    function ($request, $response) {
        $post = $request->getParsedBody();
        $ajax = isset($post['ajax']) && $post['ajax'] === 'true';
        $success = false;

        $uri = isset($post['redirect_uri']) ?
            $post['redirect_uri'] :
            $this->router->pathFor('slash');

        if (!isset($post['confirm'])) {
            $this->flash->addMessage(
                'error_detected',
                _T("Removal has not been confirmed!")
            );
        } else {
            $activity = new Activity($this->zdb, $this->login, (int)$post['id']);
            $del = $activity->remove();

            if ($del !== true) {
                $error_detected = str_replace(
                    '%name',
                    $activity->getName(),
                    _T("An error occured trying to remove activity %name :/", "events")
                );

                $this->flash->addMessage(
                    'error_detected',
                    $error_detected
                );
            } else {
                $success_detected = str_replace(
                    '%name',
                    $activity->getName(),
                    _T("Activity %name has been successfully deleted.", "events")
                );

                $this->flash->addMessage(
                    'success_detected',
                    $success_detected
                );

                $success = true;
            }
        }

        if (!$ajax) {
            return $response
                ->withStatus(301)
                ->withHeader('Location', $uri);
        } else {
            return $response->withJson(
                [
                    'success'   => $success
                ]
            );
        }
    }
)->setName('events_do_remove_activity')->add($authenticate);
