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
 * @since 2015-06-20 17:07
 */

namespace chalk\cameraman\movement;

use pocketmine\level\Location;

abstract class Movement {
    /** @var Location */
    private $origin;

    /** @var Location */
    private $destination;

    /**
     * @param Location $origin
     * @param Location $destination
     */
    public function __construct(Location $origin, Location $destination){
        $this->origin = $origin;
        $this->destination = $destination;
    }

    /**
     * @return Location
     */
    public function getOrigin(){
        return $this->origin;
    }

    /**
     * @return Location
     */
    public function getDestination(){
        return $this->destination;
    }

    public function __toString(){
        return "Movement(" . $this->getOrigin() . " -> " . $this->getDestination() . ")";
    }

    /**
     * @param number $slowness
     * @return Location|null
     */
    public abstract function tick($slowness);
}