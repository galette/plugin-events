<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Galette Events plugin
 *
 * PHP version 5
 *
 * Copyright © 2022-2023 The Galette Team
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
 *
 * @category  Plugins
 * @package   GaletteEvents
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2022-2023 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      https://galette.eu
 * @since     Available since 0.7.4dev - 2012-10-04
 */

namespace GaletteEvents;

use Galette\Core\Login;
use Galette\Entity\Adherent;
use Galette\Core\GalettePlugin;

/**
 * Galette Events plugin
 *
 * @category  Plugins
 * @name      PluginGaletteEvents
 * @package   GaletteEvents
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2022-2023 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      https://galette.eu
 * @since     Available since 0.7.4dev - 2012-10-04
 */

class PluginGaletteEvents extends GalettePlugin
{
    /**
     * Extra menus entries
     *
     * @return array|array[]
     */
    public static function getMenusContents(): array
    {
        /** @var Login $login */
        global $login;
        $menus = [];

        if ($login->isLogged()) {
            $menus['plugin_events'] = [
                'title' => _T("Events", "events"),
                'icon' => 'calendar alternate',
                'items' => [
                    [
                        'label' => _T('Events', 'events'),
                        'route' => [
                            'name' => 'events_events',
                            'aliases' => ['events_event_add', 'events_event_edit']
                        ]
                    ],
                    [
                        'label' => _T('Calendar', 'events'),
                        'route' => [
                            'name' => 'events_calendar',
                        ]
                    ],
                ]
            ];
        }

        $menus['plugin_events']['items'] = array_merge(
            $menus['plugin_events']['items'],
            [
                [
                    'label' => _T('Bookings', 'events'),
                    'route' => [
                        'name' => 'events_bookings',
                        'args' => [
                            'event' => 'all'
                        ],
                        'aliases' => ['events_booking_add', 'events_booking_edit']
                    ]
                ]
            ]
        );

        if ($login->isAdmin() || $login->isStaff()) {
            $menus['plugin_events']['items'] = array_merge(
                $menus['plugin_events']['items'],
                [
                    [
                        'label' => _T('Activities', 'events'),
                        'route' => [
                            'name' => 'events_activities',
                            'aliases' => ['events_activity_add', 'events_activity_edit']
                        ]
                    ]
                ]
            );
        }

        return $menus;
    }

    /**
     * Extra public menus entries
     *
     * @return array|array[]
     */
    public static function getPublicMenusItemsList(): array
    {
        return [];
    }

    /**
     * Get dashboards contents
     *
     * @return array|array[]
     */
    public static function getDashboardsContents(): array
    {
        return [
            [
                'label' => _T("Calendar", "events"),
                'title' => _T("Events calendar", "events"),
                'route' => [
                    'name' => 'events_calendar'
                ],
                'icon' => 'calendar_spiral'
            ]
        ];
    }

    /**
     * Get actions contents
     *
     * @param Adherent $member Member instance
     *
     * @return array|array[]
     */
    public static function getListActionsContents(Adherent $member): array
    {
        return [
            [
                'label' => _T("New event booking", "events"),
                'route' => [
                    'name' => 'events_booking_add',
                    'args' => ['id_adh' => $member->id]
                ],
                'icon' => 'calendar alternate grey'
            ],
        ];
    }

    /**
     * Get detailed actions contents
     *
     * @param Adherent $member Member instance
     *
     * @return array|array[]
     */
    public static function getDetailedActionsContents(Adherent $member): array
    {
        return static::getListActionsContents($member);
    }

    /**
     * Get batch actions contents
     *
     * @return array|array[]
     */
    public static function getBatchActionsContents(): array
    {
        return [];
    }
}
