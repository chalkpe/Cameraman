<?php

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