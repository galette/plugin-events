<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Bookings controller
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

use Galette\Entity\Adherent;
use Galette\Repository\Groups;
use Galette\Repository\Members;
use Galette\Controllers\Crud\AbstractPluginController;
use Galette\Filters\MembersList;
use GaletteEvents\Filters\BookingsList;
use GaletteEvents\Booking;
use GaletteEvents\Event;
use GaletteEvents\Repository\Bookings;
use GaletteEvents\Repository\Events;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Bookings controller
 *
 * @category  Controllers
 * @name      BookingsController
 * @package   GaletteEvents
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2021 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     2021-05-09
 */

class BookingsController extends AbstractPluginController
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
        //just for inheritance. see listBookings which signature changes.
    }

    /**
     * List page
     *
     * @param Request        $request  PSR Request
     * @param Response       $response PSR Response
     * @param mixed          $event    Linked event. May be an event ID, 'all' or 'guess'.
     * @param string         $option   One of 'page' or 'order'
     * @param string|integer $value    Value of the option
     *
     * @return Response
     */
    public function listBookings(Request $request, Response $response, $event, $option = null, $value = null): Response
    {
        $filters = $this->session->filter_bookings ?? new BookingsList();

        if ($event == 'guess') {
            $linked_event = $filters->event_filter;
        } else {
            $linked_event = $event;
        }

        if ($option !== null) {
            switch ($option) {
                case 'page':
                    $filters->current_page = (int)$value;
                    break;
                case 'order':
                    $filters->orderby = $value;
                    break;
                case 'clear_filter':
                    $filters->reinit();
                    break;
            }
        }

        $event = null;
        if ($linked_event !== 'all') {
            $filters->event_filter = (int)$linked_event;
            $event = new Event($this->zdb, $this->login, (int)$linked_event);
        }

        //Groups
        $groups = new Groups($this->zdb, $this->login);
        $groups_list = $groups->getList();

        $bookings = new Bookings($this->zdb, $this->login, $filters);

        //assign pagination variables to the template and add pagination links
        $filters->setSmartyPagination($this->router, $this->view->getSmarty(), false);

        $this->session->filter_bookings = $filters;

        $events = new Events($this->zdb, $this->login);
        $list = $bookings->getList();
        $count = $bookings->getCount();
        // display page
        $this->view->render(
            $response,
            'file:[' . $this->getModuleRoute() . ']bookings.tpl',
            [
                'page_title'        => _T("Bookings management", "events"),
                'bookings'          => $bookings,
                'bookings_list'     => $list,
                'nb_bookings'       => $count,
                'event'             => $event,
                'eventid'           => $linked_event,
                'require_dialog'    => true,
                'filters'           => $filters,
                'events'            => $events->getList(),
                'groups'            => $groups_list
            ]
        );
        return $response;
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
        //just for inheritance. see filterBookings which signature changes.
    }

    /**
     * Filtering
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     * @param mixed    $event    Linked event. May be an event ID, 'all' or 'guess'.
     *
     * @return Response
     */
    public function filterBookings(Request $request, Response $response, $event): Response
    {
        $post = $request->getParsedBody();
        if (isset($this->session->filter_bookings)) {
            $filters = $this->session->filter_bookings;
        } else {
            $filters = new BookingsList();
        }

        //reintialize filters
        if (isset($post['clear_filter'])) {
            $filters->reinit();
            $event = 'all';
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

            if (isset($post['event_filter'])) {
                if (is_numeric($post['event_filter'])) {
                    $filters->event_filter = $post['event_filter'];
                }
            }

            if (isset($post['group_filter'])) {
                if (is_numeric($post['group_filter'])) {
                    $filters->group_filter = $post['group_filter'];
                }
            }
        }

        $this->session->filter_bookings = $filters;

        return $response
            ->withStatus(301)
            ->withHeader(
                'Location',
                $this->router->pathFor('events_bookings', ['event' => $event])
            );
    }

    /**
     * Batch actions handler
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     *
     * @return Response
     */
    public function handleBatch(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();

        if (isset($post['event_sel'])) {
            if (isset($this->session->filter_bookings)) {
                $filters = clone $this->session->filter_bookings;
            } else {
                $filters = new BookingsList();
            }

            //$this->session->filter_bookings = $filters;
            $filters->selected = $post['event_sel'];

            $bookings = new Bookings($this->zdb, $this->login, $filters);
            $members = [];
            foreach ($bookings->getList() as $booking) {
                $members[] = $booking->getMemberId();
            }
            $mfilter = new MembersList();
            $mfilter->selected = $members;

            if (isset($post['mailing'])) {
                $this->session->filter_members = $mfilter;
                $this->session->redirect_mailing = $this->router->pathFor(
                    'events_bookings',
                    [
                        'event' => $filters->event_filter == null ?
                            'all' :
                            $filters->event_filter
                    ]
                );
                return $response
                    ->withStatus(301)
                    ->withHeader('Location', $this->router->pathFor('mailing') . '?new=new');
            }

            if (isset($post['csv'])) {
                $session_var = 'plugin-events-members';
                $this->session->$session_var = $mfilter;
                return $response
                    ->withStatus(307)
                    ->withHeader(
                        'Location',
                        $this->router->pathFor('csv-memberslist') . '?session_var=' . $session_var
                    );
            }

            if (isset($post['csvbooking'])) {
                $session_var = 'plugin-events-bookings';
                $this->session->$session_var = $filters;
                return $response
                    ->withStatus(307)
                    ->withHeader(
                        'Location',
                        $this->router->pathFor('events_bookings_export') . '?session_var=' . $session_var
                    );
            }

            if (isset($post['labels'])) {
                $session_var = 'plugin-events-labels';
                $this->session->$session_var = $mfilter;
                return $response
                    ->withStatus(307)
                    ->withHeader(
                        'Location',
                        $this->router->pathFor('pdf-members-labels') . '?session_var=' . $session_var
                    );
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
        $get = $request->getQueryParams();
        $route_params = [];

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
        if ($action === 'add') {
            if (isset($get['event'])) {
                $booking->setEvent((int)$get['event']);
            }
            if (
                isset($_GET[Adherent::PK]) &&
                ($this->login->isAdmin() || $this->login->isStaff() || $this->login->isGroupManager())
            ) {
                $booking->setMember((int)$_GET[Adherent::PK]);
            } elseif (
                !$this->login->isSuperAdmin()
                && !$this->login->isAdmin()
                && !$this->login->isStaff()
                && !$this->login->isGroupManager()
            ) {
                $booking->setMember($this->login->id);
            }
        }

        if (
            $this->login->isAdmin()
            || $this->login->isStaff()
            || $this->login->isGroupManager()
        ) {
            // members
            $members = [];
            $m = new Members();
            $required_fields = array(
                'id_adh',
                'nom_adh',
                'prenom_adh'
            );
            $list_members = $m->getList(false, $required_fields);

            if (count($list_members) > 0) {
                foreach ($list_members as $member) {
                    $pk = Adherent::PK;
                    $sname = mb_strtoupper($member->nom_adh, 'UTF-8') .
                        ' ' . ucwords(mb_strtolower($member->prenom_adh, 'UTF-8')) .
                        ' (' . $member->id_adh . ')';
                    $members[$member->$pk] = $sname;
                }
            }

            $route_params['members'] = [
                'filters'   => $m->getFilters(),
                'count'     => $m->getCount()
            ];
            $route_params['autocomplete'] = true;

            //check if current attached member is part of the list
            if (
                isset($booking)
                && $booking->getMemberId() > 0
                && !isset($members[$booking->getMemberId()])
            ) {
                $members[$booking->getMemberId()] = Adherent::getSName($this->zdb, $booking->getMemberId(), true);
            }

            if (count($members)) {
                $route_params['members']['list'] = $members;
            }
        } else {
            $booking->setMember($this->login->id);
        }

        // display page
        $this->view->render(
            $response,
            'file:[' . $this->getModuleRoute() . ']booking.tpl',
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
        $booking = new Booking($this->zdb, $this->login);
        if (isset($post['id']) && !empty($post['id'])) {
            $booking->load((int)$post['id']);
        }

        if (isset($post['cancel'])) {
            $redirect_url = $this->router->pathFor(
                'events_bookings',
                ['event' => 'guess']
            );
            return $response
                ->withStatus(301)
                ->withHeader('Location', $redirect_url);
        }

        $success_detected = [];
        $warning_detected = [];
        $error_detected = [];
        $goto_list = true;

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

        if (!isset($post['save'])) {
            $this->session->booking = $booking;
            $error_detected = [];
            $goto_list = false;
            $warning_detected[] = _T('Do not forget to store the booking', 'events');
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
            $redirect_url = $this->router->pathFor(
                'events_bookings',
                ['event' => $booking->getEventId()]
            );
        } else {
            //store entity in session
            $this->session->booking = $booking;

            if ($booking->getId()) {
                $route = 'events_booking_edit';
                $rparams = [
                    'id'        => $booking->getId(),
                    'action'    => 'edit'
                ];
            } else {
                $route = 'events_booking_add';
                $rparams = ['action' => 'add'];
            }
            $redirect_url = $this->router->pathFor(
                $route,
                $rparams
            );
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
        return $this->router->pathFor('events_bookings');
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
            'events_do_remove_booking',
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
        $booking = new Booking($this->zdb, $this->login, (int)$args['id']);
        $member = $booking->getMember();
        $event = $booking->getEvent();
        return sprintf(
            //TRANS: first parameter is the member name, second the event name.
            _T('Remove booking for %1$s on %2$s', 'events'),
            $member->sname,
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
        $booking = new Booking($this->zdb, $this->login, (int)$post['id']);
        return $booking->remove();
    }

    // /CRUD - Delete
    // /CRUD
}
