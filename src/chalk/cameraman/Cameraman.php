<?php

/**
 * @author ChalkPE <chalkpe@gmail.com>
 * @since 2015-06-20 17:04
 */

namespace chalk\cameraman;

use chalk\cameraman\movement\Movement;
use chalk\cameraman\movement\StraightMovement;
use chalk\utils\Messages;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Cameraman extends PluginBase implements Listener {
    /** @var Cameraman */
    private static $instance = null;

    /**
     * @return Cameraman
     */
    public static function getInstance(){
        return self::$instance;
    }

    /* ====================================================================================================================== *
     *                                                    GLOBAL VARIABLES                                                    *
     * ====================================================================================================================== */

    const TICKS_PER_SECOND = 10;
    const DELAY = 100;

    /** @var Location[][] */
    private $waypointMap = [];

    /** @var Camera[] */
    private $cameras = [];

    /* ====================================================================================================================== *
     *                                                    EVENT LISTENERS                                                     *
     * ====================================================================================================================== */

    public function onLoad(){
        self::$instance = $this;
    }

    public function onEnable(){
        $this->loadConfigs();
        $this->loadMessages();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable(){
        $this->saveConfigs();
    }

    public function onPlayerQuit(PlayerQuitEvent $event){
        if(($camera = $this->getCamera($event->getPlayer())) !== null and $camera->isRunning()){
            $camera->stop();
        }
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event){
        if($event->getPacket() instanceof MovePlayerPacket and ($camera = $this->getCamera($event->getPlayer())) !== null and $camera->isRunning()){
            $event->setCancelled(true);
        }
    }

    /* ====================================================================================================================== *
     *                                                    RESOURCE CONTROL                                                    *
     * ====================================================================================================================== */

    /** @var Messages */
    private $messages = null;
    const MESSAGE_VERSION = 1;

    public function loadMessages(){
        @mkdir($this->getDataFolder());
        $this->updateMessages("messages.yml");
        $this->messages = new Messages((new Config($this->getDataFolder() . "messages.yml", Config::YAML))->getAll());
    }

    /**
     * @param string $filename
     */
    public function updateMessages($filename = "messages.yml"){
        $this->saveResource($filename, false);

        $messages = (new Config($this->getDataFolder() . $filename, Config::YAML))->getAll();
        if(!isset($messages["version"]) or $messages["version"] < self::MESSAGE_VERSION){
            $this->saveResource($filename, true);
        }
    }

    /**
     * @return Messages
     */
    public function getMessages(){
        return $this->messages;
    }

    public function loadConfigs(){
        @mkdir($this->getDataFolder());
        $config = new Config($this->getDataFolder() . "waypoint-map.json", Config::JSON);

        foreach($config->getAll() as $key => $waypoints){
            $this->waypointMap[$key] = [];
            foreach($waypoints as $waypoint){
                $x = floatval($waypoint["x"]); $y = floatval($waypoint["y"]); $z = floatval($waypoint["y"]);
                $yaw = floatval($waypoint["yaw"]); $pitch = floatval($waypoint["pitch"]);
                $level = $this->getServer()->getLevelByName($waypoint["level"]);

                $this->waypointMap[$key][] = new Location($x, $y, $z, $yaw, $pitch, $level);
            }
        }
    }

    public function saveConfigs(){
        $waypointMap = [];

        foreach($this->getWaypointMap() as $key => $waypoints){
            $waypointMap[$key] = [];
            foreach($waypoints as $waypoint){
                $waypointMap[$key][] = [
                    "x" => $waypoint->getX(), "y" => $waypoint->getY(), "z" => $waypoint->getZ(),
                    "yaw" => $waypoint->getYaw(), "pitch" => $waypoint->getPitch(),
                    "level" => $waypoint->isValid() ? $waypoint->getLevel()->getName() : null
                ];
            }
        }

        $config = new Config($this->getDataFolder() . "waypoint-map.json", Config::JSON);
        $config->setAll($waypointMap);
        $config->save();
    }

    /* ====================================================================================================================== *
     *                                                   GETTERS AND SETTERS                                                  *
     * ====================================================================================================================== */

    /**
     * @return Location[][]
     */
    public function getWaypointMap(){
        return $this->waypointMap;
    }

    /**
     * @param Location[][] $waypointMap
     * @return Location[][]
     */
    public function setWaypointMap(array $waypointMap){
        $this->waypointMap = $waypointMap;
        return $waypointMap;
    }

    /**
     * @param Player $player
     * @return Location[]
     */
    public function getWaypoints(Player $player){
        return isset($this->waypointMap[$player->getName()]) ? $this->waypointMap[$player->getName()] : null;
    }

    /**
     * @param Player $player
     * @param Location[] $waypoints
     * @return Location[]
     */
    public function setWaypoints(Player $player, array $waypoints){
        $this->waypointMap[$player->getName()] = $waypoints;
        return $waypoints;
    }

    /**
     * @param Player $player
     * @param Location $waypoint
     * @param int $index
     * @return Location[]
     */
    public function setWaypoint(Player $player, Location $waypoint, $index = -1){
        if($index >= 0){
            $this->waypointMap[$player->getName()][$index] = $waypoint;
        }else{
            $this->waypointMap[$player->getName()][] = $waypoint;
        }
        return $this->waypointMap[$player->getName()];
    }

    /**
     * @return Camera[]
     */
    public function getCameras(){
        return $this->cameras;
    }

    /**
     * @param Player $player
     * @return Camera|null
     */
    public function getCamera(Player $player){
        return isset($this->cameras[$player->getName()]) ? $this->cameras[$player->getName()] : null;
    }

    /**
     * @param Player $player
     * @param Camera $camera
     * @return Camera
     */
    public function setCamera(Player $player, Camera $camera){
        $this->cameras[$player->getName()] = $camera;
        return $camera;
    }

    /* ====================================================================================================================== *
     *                                                     HELPER METHODS                                                     *
     * ====================================================================================================================== */

    /**
     * @param Location[] $waypoints
     * @return Movement[]
     */
    public static function createStraightMovements(array $waypoints){
        $lastWaypoint = null;

        $movements = [];
        foreach($waypoints as $waypoint){
            if($lastWaypoint !== null and !$waypoint->equals($lastWaypoint)){
                $movements[] = new StraightMovement($lastWaypoint, $waypoint);
            }
            $lastWaypoint = $waypoint;
        }
        return $movements;
    }

    /**
     * @param Player $player
     * @return bool|int
     */
    public static function sendMovePlayerPacket(Player $player){
        $packet = new MovePlayerPacket();
        $packet->eid = 0;
        $packet->x = $player->getX();
        $packet->y = $player->getY();
        $packet->z = $player->getZ();
        $packet->yaw = $player->getYaw();
        $packet->bodyYaw = $player->getYaw();
        $packet->pitch = $player->getPitch();
        $packet->onGround = false;

        return $player->dataPacket($packet);
    }

    /* ====================================================================================================================== *
     *                                                     MESSAGE SENDERS                                                    *
     * ====================================================================================================================== */

    private static $colorError = TextFormat::RESET . TextFormat::RED;
    private static $colorLight = TextFormat::RESET . TextFormat::GREEN;
    private static $colorDark  = TextFormat::RESET . TextFormat::DARK_GREEN;
    private static $colorTitle = TextFormat::RESET . TextFormat::DARK_GREEN . TextFormat::BOLD;
    private static $colorTITLE = TextFormat::RESET . TextFormat::RED        . TextFormat::BOLD;

    private static $commands = [
        "p", "start", "stop", "info", "goto", "clear", "help", "about"
    ];

    private static $commandMap = [
        "1" => ["p", "start", "stop"],
        "2" => ["info", "goto", "clear"],
        "3" => ["help", "about"]
    ];

    /**
     * @param CommandSender $sender
     * @param string $key
     * @param array $format
     * @return bool
     */
    public function sendMessage(CommandSender $sender, $key, $format = []){
        if($sender === null){
            return false;
        }

        if($key[0] === '@'){
            $key = substr($key, 1);
            $prefix = Cameraman::$colorTitle;
        }else if($key[0] === '!'){
            $key = substr($key, 1);
            $prefix = Cameraman::$colorDark;
        }else if($key[0] === '?'){
            $key = substr($key, 1);
            $prefix = Cameraman::$colorLight;
        }else if ($key[0] === '.'){
            $key = substr($key, 1);
            $prefix = Cameraman::$colorTitle . $this->getMessages()->getMessage("prefix") . Cameraman::$colorDark;
        }else if ($key[0] === '#'){
            $key = substr($key, 1);
            $prefix = Cameraman::$colorTITLE . $this->getMessages()->getMessage("prefix") . Cameraman::$colorError;
        }else{
            $prefix = Cameraman::$colorTitle . $this->getMessages()->getMessage("prefix") . Cameraman::$colorLight;
        }

        $sender->sendMessage($prefix . $this->getMessages()->getMessage($key, $format));
        return true;
    }

    /**
     * @param CommandSender $sender
     * @return bool
     */
    public function sendAboutMessages(CommandSender $sender){
        $this->sendMessage($sender, "@message-about", ["version" => $this->getDescription()->getVersion(), "chalkpe" => $this->getDescription()->getAuthors()[0], "website" => $this->getDescription()->getWebsite()]);
        return true;
    }

    /**
     * @param CommandSender $sender
     * @return bool
     */
    public function sendUnknownCommandErrorMessage(CommandSender $sender){
        $this->sendMessage($sender, "#error-unknown-command-0");
        $this->sendMessage($sender, "#error-unknown-command-1");
        return true;
    }

    /**
     * @param CommandSender $sender
     * @param string $param
     * @return bool
     */
    public function sendHelpMessages(CommandSender $sender, $param = "1"){
        $param = strToLower($param);

        if($param === ""){
            foreach(Cameraman::$commands as $command){
                $this->sendMessage($sender,  "command-" . $command . "-usage");
                $this->sendMessage($sender, ".command-" . $command . "-description");
            }
            return true;
        }

        if(is_numeric($param)){
            $this->sendMessage($sender, "@message-help", ["current" => $param, "total" => count(Cameraman::$commandMap)]);
            if(isset(Cameraman::$commandMap[$param])){
                foreach(Cameraman::$commandMap[$param] as $command){
                    $this->sendMessage($sender,  "command-" . $command . "-usage");
                    $this->sendMessage($sender, ".command-" . $command . "-description");
                }
            }
            return true;
        }

        if(in_array($param, Cameraman::$commands)){
            $this->sendMessage($sender,  "command-" . $param . "-usage");
            $this->sendMessage($sender, ".command-" . $param . "-description");

            return true;
        }

        return $this->sendUnknownCommandErrorMessage($sender);
    }

    /**
     * @param CommandSender $sender
     * @param Vector3 $waypoint
     * @param int $index
     * @return bool
     */
    public function sendWaypointMessage(CommandSender $sender, Vector3 $waypoint, $index){
        $this->sendMessage($sender, "message-waypoint-info", ["index" => $index, "x" => $waypoint->getFloorX(), "y" => $waypoint->getFloorY(), "z" => $waypoint->getFloorZ()]);

        return true;
    }

    /* ====================================================================================================================== *
     *                                                    COMMAND HANDLERS                                                    *
     * ====================================================================================================================== */

    /**
     * @param int $index
     * @param array $array
     * @param CommandSender $sender
     * @return bool
     */
    public function checkIndex($index, array $array, CommandSender $sender = null){
        if($index < 1 or $index > count($array)){
            $this->sendMessage($sender, "#error-index-out-of-bounds", ["total" => count($array)]);
            return true;
        }
        return false;
    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $commandAlias
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, $commandAlias, array $args){
        if(!$sender instanceof Player){
            $this->sendMessage($sender, "#error-only-in-game");
            return true;
        }

        if($commandAlias === "p"){ //shortcut for /cam p
            array_unshift($args, "p");
        }

        if(count($args) < 1){
            return $this->sendHelpMessages($sender);
        }

        switch(strToLower($args[0])){
            default:
                $this->sendUnknownCommandErrorMessage($sender);
                break;

            case "help":
                if(count($args) > 1){
                    return $this->sendHelpMessages($sender, $args[1]);
                }else{
                    return $this->sendHelpMessages($sender);
                }

            case "about":
                return $this->sendAboutMessages($sender);

            case "p":
                if(($waypoints = $this->getWaypoints($sender)) === null){
                    $waypoints = $this->setWaypoints($sender, []);
                }

                if(count($args) > 1 and is_numeric($args[1])){
                    if($this->checkIndex($index = intval($args[1]), $waypoints, $sender)){
                        return true;
                    }

                    $waypoints = $this->setWaypoint($sender, $sender->getLocation(), $index - 1);
                    $this->sendMessage($sender, "message-reset-waypoint", ["index" => $index, "total" => count($waypoints)]);
                }else{
                    $waypoints = $this->setWaypoint($sender, $sender->getLocation());
                    $this->sendMessage($sender, "message-added-waypoint", ["index" => count($waypoints)]);
                }
                break;

            case "start":
                if(count($args) < 2 or !is_numeric($args[1])){
                    return $this->sendHelpMessages($sender, $args[0]);
                }

                if(($waypoints = $this->getWaypoints($sender)) === null or count($waypoints) < 2){
                    $this->sendMessage($sender, "#error-too-few-waypoints");
                    return $this->sendHelpMessages($sender, "p");
                }

                if(($slowness = doubleval($args[1])) < 0.0000001){
                    return $this->sendMessage($sender, "#error-negative-slowness", ["slowness" => $slowness]);
                }

                if(($camera = $this->getCamera($sender)) !== null and $camera->isRunning()){
                    $this->sendMessage($sender, ".message-interrupting-current-travel");
                    $camera->stop();
                }

                $this->setCamera($sender, new Camera($sender, Cameraman::createStraightMovements($waypoints), $slowness))->start();
                break;

            case "stop":
                if(($camera = $this->getCamera($sender)) === null or !$camera->isRunning()){
                    return $this->sendMessage($sender, "#error-travels-already-interrupted");
                }

                $camera->stop(); unset($camera);
                $this->sendMessage($sender, "message-travelling-interrupted");
                break;

            case "info":
                if(($waypoints = $this->getWaypoints($sender)) === null or count($waypoints) === 0){
                    return $this->sendMessage($sender, "#error-no-waypoints-to-show");
                }

                if(count($args) > 1 and is_numeric($args[1])){
                    if($this->checkIndex($index = intval($args[1]), $waypoints, $sender)){
                        return true;
                    }

                    $this->sendWaypointMessage($sender, $waypoints[$index - 1], $index);
                }else{
                    foreach($waypoints as $index => $waypoint){
                        $this->sendWaypointMessage($sender, $waypoint, $index + 1);
                    }
                }
                break;

            case "goto":
                if(count($args) < 2 or !is_numeric($args[1])){
                    return $this->sendHelpMessages($sender, $args[0]);
                }

                if(($waypoints = $this->getWaypoints($sender)) === null or count($waypoints) === 0){
                    return $this->sendMessage($sender, "#error-no-waypoints-to-teleport");
                }

                if($this->checkIndex($index = intval($args[1]), $waypoints, $sender)){
                    return true;
                }

                $sender->teleport($waypoints[$index - 1]);
                $this->sendMessage($sender, "message-teleported", ["index" => $index]);
                break;

            case "clear":
                if(($waypoints = $this->getWaypoints($sender)) === null or count($waypoints) === 0){
                    return $this->sendMessage($sender, "#error-no-waypoints-to-remove");
                }

                if(count($args) > 1 and is_numeric($args[1])){
                    if($this->checkIndex($index = intval($args[1]), $waypoints, $sender)){
                        return true;
                    }

                    array_splice($waypoints, $index - 1, 1);
                    $this->sendMessage($sender, "message-removed-waypoint", ["index" => $index, "total" => count($waypoints)]);
                }else{
                    unset($waypoints);
                    $this->sendMessage($sender, "message-all-waypoint-removed");
                }
                break;
        }
        return true;
    }
}