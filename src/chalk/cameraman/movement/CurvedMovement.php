<?php

/*
 * Copyright (C) 2015  ChalkPE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @author ChalkPE <chalkpe@gmail.com>
 * @since 2015-07-02 14:11
 */

namespace chalk\cameraman\movement;

use pocketmine\level\Location;

class CurvedMovement extends StraightMovement {
    /**
     * @param Location $origin
     * @param Location $destination
     */
    public function __construct(Location $origin, Location $destination){
        parent::__construct($origin, $destination);
    }

    /**
     * @param number $slowness
     * @param float $curve
     * @return Location|null
     */
    public function tick($slowness, $curve = 5.0){
        if(($location = parent::tick($slowness)) !== null){
            $offset = $curve * sin(M_PI * ($this->current / ($this->length * $slowness)));
            $location->setComponents($location->getX() + $offset, $location->getY(), $location->getZ() + $offset);
        }
        return $location;
    }
}