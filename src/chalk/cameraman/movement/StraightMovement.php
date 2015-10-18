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
 * @since 2015-06-20 17:24
 */

namespace chalk\cameraman\movement;

use chalk\cameraman\Cameraman;
use pocketmine\level\Location;

class StraightMovement extends Movement {
    /** @var Location */
    private $distance;

    /** @var int */
    protected $current = 0, $length = 0;

    /**
     * @param Location $origin
     * @param Location $destination
     */
    function __construct(Location $origin, Location $destination){
        parent::__construct($origin, $destination);

        $this->distance = new Location($this->getDestination()->getX() - $this->getOrigin()->getX(), $this->getDestination()->getY() - $this->getOrigin()->getY(), $this->getDestination()->getZ() - $this->getOrigin()->getZ(), $this->getDestination()->getYaw() - $this->getOrigin()->getYaw(), $this->getDestination()->getPitch() - $this->getOrigin()->getPitch());
        $this->length = Cameraman::TICKS_PER_SECOND * max(abs($this->distance->getX()), abs($this->distance->getY()), abs($this->distance->getZ()), abs($this->distance->getYaw()), abs($this->distance->getPitch()));
    }

    /**
     * @param number $slowness
     * @return Location|null
     */
    public function tick($slowness){
        if(($length = $this->length * $slowness) < 0.0000001){
            return null;
        }

        if(($progress = $this->current++ / $length) > 1){
            return null;
        }

        return new Location($this->getOrigin()->getX() + $this->distance->getX() * $progress, 1.62 + $this->getOrigin()->getY() + $this->distance->getY() * $progress, $this->getOrigin()->getZ() + $this->distance->getZ() * $progress, $this->getOrigin()->getYaw() + $this->distance->getYaw() * $progress, $this->getOrigin()->getPitch() + $this->distance->getPitch() * $progress);
    }
}