<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Events controller
 *
 * PHP version 5
 *
 * Copyright © 2021 The Galette Team
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
 * @category  Controllers
 * @package   GaletteEvents
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2021 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     2021-05-09
 */

namespace GaletteEvents\Controllers\Crud;

use Galette\Repository\Groups;
use Galette\Controllers\Crud\AbstractPluginController;
use GaletteEvents\Filters\EventsList;
use GaletteEvents\Event;
use GaletteEvents\Repository\Events;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Events controller
 *
 * @category  Controllers
 * @name      EventsController
 * @package   GaletteEvents
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2021 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     2021-05-09
 */

class EventsController extends AbstractPluginController
{
    /**
     * @Inject("Plugin Galette Events")
     * @var integer
     */
    protected $module_info;

    // CRUD - Create

    /**
     * Add page
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     *
     * @return Response
     */
    public function add(Request $request, Response $response): Response
    {
        return $this->edit($request, $response, null, 'add');
    }

    /**
     * Add action
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     *
     * @return Response
     */
    public function doAdd(Request $request, Response $response): Response
    {
        return $this->doEdit($request, $response, null, 'add');
    }

    // /CRUD - Create
    // CRUD - Read

    /**
     * List page
     *
     * @param Request        $request  PSR Request
     * @param Response       $response PSR Response
     * @param string         $option   One of 'page' or 'order'
     * @param string|integer $value    Value of the option
     *
     * @return Response
     */
    public function list(Request $request, Response $response, $option = null, $value = null): Response
    {
        if (isset($this->session->filter_events)) {
            $filters = $this->session->filter_events;
        } else {
            $filters = new EventsList();
        }

        if ($option !== null) {
            switch ($option) {
                case 'page':
                    $filters->current_page = (int)$value;
                    break;
                case 'order':
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
            'file:[' . $this->getModuleRoute() . ']events.tpl',
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

    /**
     * Calendar view
     *
     * @param Request        $request  PSR Request
     * @param Response       $response PSR Response
     * @param string         $option   One of 'page' or 'order'
     * @param string|integer $value    Value of the option
     *
     * @return Response
     */
    public function calendar(Request $request, Response $response, $option = null, $value = null): Response
    {
        $option = null;
        if (isset($args['option'])) {
            $option = $args['option'];
        }
        $value = null;
        if (isset($args['value'])) {
            $value = $args['value'];
        }

        if (isset($this->session->filter_events_calendar)) {
            $filters = $this->session->filter_events_calendar;
        } else {
            $filters = new EventsList();
        }
        $filters->calendar_filter = true;

        if ($option !== null) {
            switch ($option) {
                case 'page':
                    $filters->current_page = (int)$value;
                    break;
                case 'order':
                    $filters->orderby = $value;
                    break;
            }
        }

        $events = new Events($this->zdb, $this->login, $filters);

        //assign pagination variables to the template and add pagination links
        $filters->setSmartyPagination($this->router, $this->view->getSmarty(), false);

        $this->session->filter_events_calendar = $filters;

        //check if JS has been generated
        if (!file_exists(__DIR__ . '/../../../../webroot/js/calendar.bundle.js')) {
            $this->flash->addMessageNow(
                'error_detected',
                _T('Javascript libraries has not been built!', 'events')
            );
        }

        // display page
        $this->view->render(
            $response,
            'file:[' . $this->getModuleRoute() . ']calendar.tpl',
            array(
                'page_title'            => _T("Events calendar", "events"),
                'require_dialog'        => true,
                'events'                => $events->getList(),
                'nb_events'             => $events->getCount(),
                'filters'               => $filters,
                'module_id'             => $this->getModuleId()
            )
        );
        return $response;
    }

    /**
     * Calendar view
     *
     * @param Request        $request  PSR Request
     * @param Response       $response PSR Response
     * @param string         $option   One of 'page' or 'order'
     * @param string|integer $value    Value of the option
     *
     * @return Response
     */
    public function ajaxCalendar(Request $request, Response $response, $option = null, $value = null): Response
    {
        $get = $request->getQueryParams();
        $filters = $this->session->filter_events_calendar ?? new EventsList();
        $filters->calendar_filter = true;
        $filters->start_date_filter = date(__("Y-m-d"), strtotime($get['start']));
        $filters->end_date_filter = date(__("Y-m-d"), strtotime($get['end']));

        $events = new Events($this->zdb, $this->login, $filters);

        return $response->withJson($events->getList());
    }

    /**
     * Filtering
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     *
     * @return Response
     */
    public function filter(Request $request, Response $response): Response
    {
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

    // /CRUD - Read
    // CRUD - Update

    /**
     * Edit page
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     * @param int|null $id       Model id
     * @param string   $action   Action
     *
     * @return Response
     */
    public function edit(Request $request, Response $response, int $id = null, $action = 'edit'): Response
    {
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
        $title = _T("Event", "events");
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
            'file:[' . $this->getModuleRoute() . ']event.tpl',
            array(
                'autocomplete'      => true,
                'page_title'        => $title,
                'event'             => $event,
                'require_calendar'  => true,
                // pseudo random int
                'time'              => time(),
                'groups'            => $groups_list,
            )
        );
        return $response;
    }

    /**
     * Edit action
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     * @param null|int $id       Model id for edit
     * @param string   $action   Either add or edit
     *
     * @return Response
     */
    public function doEdit(Request $request, Response $response, int $id = null, $action = 'edit'): Response
    {
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

            if (isset($post['add_activity']) || isset($post['remove_activity'])) {
                $this->session->event = $event;
                if (isset($post['add_activity'])) {
                    $success_detected[] = _T("Activity has been attached to event.", "events");
                    $warning_detected[] = _T('Do not forget to store the event', 'events');
                } else {
                    $success_detected[] = _T("Activity has been detached from event.", "events");
                }
                $goto_list = false;
            }
            if (isset($post['save']) || isset($post['remove_activity'])) {
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
            }
        }

        if (!isset($post['save'])) {
            $this->session->event = $event;
            $error_detected = [];
            $goto_list = false;
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
                $redirect_url = $this->router->pathFor(
                    'events_event_edit',
                    ['id' => $event->getId()]
                );
            } else {
                $redirect_url = $this->router->pathFor('events_event_add');
            }
        }

        return $response
            ->withStatus(301)
            ->withHeader('Location', $redirect_url);
    }

    // /CRUD - Update
    // CRUD - Delete

    /**
     * Get redirection URI
     *
     * @param array $args Route arguments
     *
     * @return string
     */
    public function redirectUri(array $args): string
    {
        return $this->router->pathFor('events_events');
    }

    /**
     * Get form URI
     *
     * @param array $args Route arguments
     *
     * @return string
     */
    public function formUri(array $args): string
    {
        return $this->router->pathFor(
            'events_do_remove_event',
            $args
        );
    }

    /**
     * Get confirmation removal page title
     *
     * @param array $args Route arguments
     *
     * @return string
     */
    public function confirmRemoveTitle(array $args): string
    {
        $event = new Event($this->zdb, $this->login, (int)$args['id']);
        return sprintf(
            //TRANS: first parameter is the event name
            _T('Remove event \'%1$s\'"', 'events'),
            $event->getName()
        );
    }

    /**
     * Remove object
     *
     * @param array $args Route arguments
     * @param array $post POST values
     *
     * @return boolean
     */
    protected function doDelete(array $args, array $post): bool
    {
        $event = new Event($this->zdb, $this->login, (int)$post['id']);
        return $event->remove();
    }

    // /CRUD - Delete
    // /CRUD
}
