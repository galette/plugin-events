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

$this->get(
    __('/events', 'events_routes'),
    function ($request, $response, $args) use ($module, $module_id) {
        // display page
        $this->view->render(
            $response,
            'file:[' . $module['route'] . ']events.tpl',
            []
        );
        return $response;
    }
)->setName('events_events')->add($authenticate);

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
