<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2018-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteEvents\Filters;

use Analog\Analog;
use Galette\Core\Pagination;
use Galette\Enums\SQLOrder;
use GaletteEvents\Repository\Activities;

/**
 * Events lists filters and paginator
 *
 * @author sJohan Cwiklinski <johan@x-tnd.be>
 *
 * @property string $query
 */

class ActivitiesList extends Pagination
{
    //filters
    private ?string $name_filter = null;
    private ?bool $active_filter = null;
    private string $query;

    /** @var array<string> */
    protected array $list_fields = [
        'name_filter',
        'active_filter'
    ];

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->reinit();
    }

    /**
     * Returns the field we want to default set order to
     *
     * @return int|string field name
     */
    protected function getDefaultOrder(): int|string
    {
        return Activities::ORDERBY_DATE;
    }

    /**
     * Return the default direction for ordering
     */
    protected function getDefaultDirection(): SQLOrder
    {
        return SQLOrder::DESC;
    }

    /**
     * Reinit default parameters
     */
    public function reinit(): void
    {
        parent::reinit();
        $this->name_filter = null;
        $this->active_filter = null;
    }

    /**
     * Global getter method
     *
     * @param string $name name of the property we want to retrieve
     *
     * @return mixed the called property
     */
    public function __get(string $name): mixed
    {
        if (in_array($name, $this->pagination_fields)) {
            return parent::__get($name);
        } else {
            if (in_array($name, $this->list_fields)) {
                return $this->$name;
            }
        }

        throw new \RuntimeException(
            sprintf(
                'Unable to get property "%s::%s"!',
                __CLASS__,
                $name
            )
        );
    }

    /**
     * Global setter method
     *
     * @param string $name  name of the property we want to assign a value to
     * @param mixed  $value a relevant value for the property
     */
    public function __set(string $name, mixed $value): void
    {
        if (in_array($name, $this->pagination_fields)) {
            parent::__set($name, $value);
        } else {
            Analog::log(
                '[ActivitiesList] Setting property `' . $name . '`',
                Analog::DEBUG
            );

            $this->$name = $value;
        }
    }
}
