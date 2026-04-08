<?php

/**
 * Copyright © 2003-2026 The Galette Team
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

declare(strict_types=1);

namespace GaletteEvents;

use DI\Attribute\Inject;
use Galette\Core\Db;
use Galette\Core\Login;
use Galette\Entity\Adherent;
use Galette\Core\GalettePlugin;
use Galette\IO\News\Entry;
use Galette\IO\News\Post;
use GaletteEvents\Filters\EventsList;
use GaletteEvents\Repository\Events;

/**
 * Galette Events plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */

class PluginGaletteEvents extends GalettePlugin implements \Galette\Core\Plugins\NewsProviderInterface
{
    #[Inject]
    protected Db $zdb;

    #[Inject]
    protected Login $login;

    /**
     * Extra menus entries
     *
     * @return array<string, string|array<string, mixed>>
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
     * @return array<int, string|array<string, mixed>>
     */
    public static function getPublicMenusItemsList(): array
    {
        return [];
    }

    /**
     * Get dashboards contents
     *
     * @return array<int, string|array<string, mixed>>
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
     * @return array<int, string|array<string, mixed>>
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
     * @return array<int, string|array<string, mixed>>
     */
    public static function getDetailedActionsContents(Adherent $member): array
    {
        return static::getListActionsContents($member);
    }

    /**
     * Get batch actions contents
     *
     * @return array<int, string|array<string, mixed>>
     */
    public static function getBatchActionsContents(): array
    {
        return [];
    }

    /**
     * Get current logged-in user dashboards contents
     *
     * @return array<int, string|array<string,mixed>>
     */
    public static function getMyDashboardsContents(): array
    {
        return [];
    }

    /**
     * Get plugin upcoming events
     */
    public function getNews(): ?Entry
    {
        $filters = new EventsList();
        $now = new \DateTime();
        $filters->start_date_filter = $now->format(_T('Y-m-d'));
        $events = new Events($this->zdb, $this->login, $filters);

        $posts = [];
        $list = $events->getList();
        if (!count($list)) {
            return null;
        }

        foreach ($list as $event) {
            $posts[] = new Post(
                title: $event->getName(),
                date: $event->getBeginDate()
            );
        }

        return new Entry(
            _T('Upcoming events', 'events'),
            $posts
        );
    }

    /**
     * Is the plugin fully installed (including database, extra configuration, etc)?
     */
    public function isInstalled(): bool
    {
        return $this->zdb->tableExists(EVENTS_PREFIX . 'events');
    }
}
