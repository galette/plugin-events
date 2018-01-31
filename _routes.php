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
use GaletteEvents\Event;
use GaletteEvents\Repository\Events;

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
                ->withHeader('Location', $this->router->pathFor('event', ['action' => __('add', 'routes')]));
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

$this->get(
    __('/bookings', 'events_routes'),
    function ($request, $response) use ($module, $module_id) {
        // display page
        $this->view->render(
            $response,
            'file:[' . $module['route'] . ']bookings.tpl',
            []
        );
        return $response;
    }
)->setName('events_bookings');
