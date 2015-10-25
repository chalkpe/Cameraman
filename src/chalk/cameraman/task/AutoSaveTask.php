<?php

/**
 * @author ChalkPE <chalkpe@gmail.com>
 * @since 2015-10-25 19:25
 */

namespace chalk\cameraman\task;


use chalk\cameraman\Cameraman;
use pocketmine\scheduler\PluginTask;

class AutoSaveTask extends PluginTask {
    public function __construct(){
        parent::__construct(Cameraman::getInstance());
    }

    public function onRun($currentTick){
        Cameraman::getInstance()->saveConfigs();
    }
}