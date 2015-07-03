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

	public function tick($slowness){
		return parent::tick($slowness); //TODO: Change the auto-generated stub
	}
}