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
 * @since 2015-06-21 16:36
 */

namespace chalk\cameraman\task;

use chalk\cameraman\Camera;
use chalk\cameraman\Cameraman;
use pocketmine\scheduler\PluginTask;

class CameraTask extends PluginTask {
    /** @var Camera */
    private $camera;

    /** @var int */
    private $index = -1;

    function __construct(Camera $camera){
        parent::__construct(Cameraman::getInstance());
        $this->camera = $camera;
    }

    /**
     * @param $currentTick
     */
    public function onRun($currentTick){
        if($this->index < 0){
            Cameraman::getInstance()->sendMessage($this->getCamera()->getTarget(), "message-travelling-started", ["slowness" => $this->getCamera()->getSlowness()]);
            $this->index = 0;
        }

        if($this->index >= count($this->getCamera()->getMovements())){
            $this->getCamera()->stop();
            return;
        }

        if(($location = $this->getCamera()->getMovement($this->index)->tick($this->getCamera()->getSlowness())) === null){
            $this->index++;
            return;
        }

        $this->getCamera()->getTarget()->setPositionAndRotation($location, $location->getYaw(), $location->getPitch());
        Cameraman::sendMovePlayerPacket($this->getCamera()->getTarget());
    }

    /**
     * @return Camera
     */
    public function getCamera(){
        return $this->camera;
    }
}
