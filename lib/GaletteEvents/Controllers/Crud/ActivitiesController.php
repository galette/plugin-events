<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Activities controller
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

use Galette\Controllers\Crud\AbstractPluginController;
use GaletteEvents\Filters\ActivitiesList;
use GaletteEvents\Activity;
use GaletteEvents\Repository\Activities;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Activities controller
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

class ActivitiesController extends AbstractPluginController
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
        if (isset($this->session->filter_activities)) {
            $filters = $this->session->filter_activities;
        } else {
            $filters = new ActivitiesList();
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
            'file:[' . $this->getModuleRoute() . ']activities.tpl',
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
        //no filter
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
        $title = _T("Activity", "events");
        if ($activity->getId() != '') {
            $title .= ' (' . _T("modification") . ')';
        } else {
            $title .= ' (' . _T("creation") . ')';
        }

        // display page
        $this->view->render(
            $response,
            'file:[' . $this->getModuleRoute() . ']activity.tpl',
            array(
                'autocomplete'  => true,
                'page_title'    => $title,
                'activity'      => $activity,
                // pseudo random int
                'time'          => time()
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
                $redirect_url = $this->router->pathFor(
                    'events_activity_edit',
                    ['id' => $activity->getId()]
                );
            } else {
                $redirect_url = $this->router->pathFor('events_activity_add');
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
        return $this->router->pathFor('events_activities');
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
            'events_do_remove_activity',
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
        $activity = new Activity($this->zdb, $this->login, (int)$args['id']);
        return sprintf(
            _T('Remove activity %1$s', 'events'),
            $activity->getName()
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
        $activity = new Activity($this->zdb, $this->login, (int)$args['id']);
        return $activity->remove();
    }

    // /CRUD - Delete
    // /CRUD
}
