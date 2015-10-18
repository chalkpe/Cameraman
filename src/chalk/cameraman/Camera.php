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
 * @since 2015-06-21 16:10
 */

namespace chalk\cameraman;

use chalk\cameraman\movement\Movement;
use chalk\cameraman\task\CameraTask;
use pocketmine\level\Location;
use pocketmine\Player;

class Camera {
    /** @var Player */
    private $target;

    /** @var Movement[] */
    private $movements = [];

    /** @var number */
    private $slowness;

    /** @var int */
    private $taskId = -1;

    /** @var int */
    private $gamemode;

    /** @var Location */
    private $location;

    /**
     * @param Player $target
     * @param Movement[] $movements
     * @param number $slowness
     */
    function __construct(Player $target, array $movements, $slowness){
        $this->target = $target;
        $this->movements = $movements;
        $this->slowness = $slowness;
    }

    /**
     * @return Player
     */
    public function getTarget(){
        return $this->target;
    }

    /**
     * @return Movement[]
     */
    public function getMovements(){
        return $this->movements;
    }

    /**
     * @param int $index
     * @return Movement
     */
    public function getMovement($index){
        return $this->movements[$index];
    }

    /**
     * @return number
     */
    public function getSlowness(){
        return $this->slowness;
    }

    public function isRunning(){
        return $this->taskId !== -1;
    }

    public function start(){
        if(!$this->isRunning()){
            Cameraman::getInstance()->sendMessage($this->getTarget(), "message-travelling-will-start");

            $this->location = $this->getTarget()->getLocation();
            $this->gamemode = $this->getTarget()->getGamemode();

            $this->getTarget()->setGamemode(Player::SPECTATOR);

            $this->taskId = Cameraman::getInstance()->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new CameraTask($this), Cameraman::DELAY, 20 / Cameraman::TICKS_PER_SECOND)->getTaskId();
        }
    }

    public function stop(){
        if($this->isRunning()){
            Cameraman::getInstance()->getServer()->getScheduler()->cancelTask($this->taskId); $this->taskId = -1;

            $this->getTarget()->teleport($this->location);
            $this->getTarget()->setGamemode($this->gamemode);

            Cameraman::getInstance()->sendMessage($this->getTarget(), "message-travelling-finished");
        }
    }
}