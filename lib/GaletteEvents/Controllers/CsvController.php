<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * CSV controller for events plugins
 *
 * PHP version 5
 *
 * Copyright © 2020 The Galette Team
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
 * @copyright 2020 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */

namespace GaletteEvents\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;

use Galette\Entity\Adherent;
use Galette\Filters\MembersList;
use Galette\IO\Csv;
use Galette\IO\CsvOut;
use Galette\Repository\Groups;
use Galette\Repository\Members;

use GaletteEvents\Activity;
use GaletteEvents\Booking;
use GaletteEvents\Event;
use GaletteEvents\Filters\ActivitiesList;
use GaletteEvents\Filters\BookingsList;
use GaletteEvents\Filters\EventsList;
use GaletteEvents\Repository\Activities;
use GaletteEvents\Repository\Bookings;
use GaletteEvents\Repository\Events;

use Analog\Analog;

/**
 * CSV controller for events plugin
 *
 * @category  Controllers
 * @name      CsvController
 * @package   GaletteEvents
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2020 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */
class CsvController extends \Galette\Controllers\CsvController
{
    /**
     * Bookings CSV exports
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     * @param array    $args     Request arguments
     *
     * @return Response
     */
    public function bookingsExport(Request $request, Response $response, array $args = []) :Response
    {
        $post = $request->getParsedBody();
        $get = $request->getQueryParams();
        $csv = new CsvOut();


        $session_var = $post['session_var'] ?? $get['session_var'] ?? 'filter_bookings';
        if (isset($this->session->$session_var)) {
            $filters = $this->session->$session_var;
        } else {
            $filters = new BookingsList();
        }

        if (isset($args['id'])) {
            $filters->event_filter = $args['id'];
        }

        $bookings = new Bookings($this->zdb, $this->login, $filters);
        $bookings_list = $bookings->getList(true);

        $labels = [
            _T('Name'),
            _T('First name'),
            _T('Address'),
            _T('Address (continuation)'),
            _T('Zip code', 'events'),
            _T('City'),
            _T('Phone'),
            _T('GSM'),
            _T('Email'),
            _T('Number of persons', 'events'),
        ];

        //activities are onl:y available for one event
        if (isset($args['id'])) {
            $event = new Event($this->zdb, $this->login, (int)$args['id']);
            $activities = $event->getActivities();
            foreach ($activities as $activity) {
                $labels[] = $activity['activity']->getName();
            }

            $labels = array_merge(
                $labels,
                [
                    _T('Amount', 'events'),
                    _T('Payment type'),
                    _T('Bank name', 'events'),
                    _T('Check number', 'events'),
                ]
            );
        }

        //prepare labels to work with external soft: requires no accent and MAJ
        foreach ($labels as &$label) {
            $string = htmlentities($label, ENT_NOQUOTES, 'utf-8');
            $string = preg_replace(
                '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#',
                '\1',
                $string
            );
            $string = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $string);
            $string = preg_replace('#&[^;]+;#', '', $string);
            $label = strtoupper($string);
        }

        $list = [];
        foreach ($bookings_list as $booking) {
            $member = $booking->getMember();
            $entry = [
                $member->name,
                $member->surname,
                $member->address,
                $member->address_continuation,
                $member->zipcode,
                $member->town,
                $member->phone,
                $member->gsm,
                $member->email,
                $booking->getNumberPeople()
            ];

            if (isset($args['id'])) {
                $bactivities = $booking->getActivities();
                foreach (array_keys($activities) as $aid) {
                    $entry[] = isset($bactivities[$aid]) && $bactivities[$aid]['checked'] ? _T('Yes') : _T('No');
                }

                $entry = array_merge(
                    $entry,
                    [
                        $booking->getAmount(),
                        $booking->getPaymentMethodName(),
                        $booking->getBankName(),
                        $booking->getCheckNumber()
                    ]
                );
            }

            $list[] = $entry;
        }

        $filename = 'bookingslist.csv';
        $filepath = CsvOut::DEFAULT_DIRECTORY . $filename;
        $fp = fopen($filepath, 'w');
        if ($fp) {
            $res = $csv->export(
                $list,
                Csv::DEFAULT_SEPARATOR,
                Csv::DEFAULT_QUOTE,
                $labels,
                $fp
            );
            fclose($fp);
            $written[] = array(
                'name' => $filename,
                'file' => $filepath
            );
        }

        $filepath = CsvOut::DEFAULT_DIRECTORY . $filename;
        return $this->sendResponse($response, $filepath, $filename);
    }
}
