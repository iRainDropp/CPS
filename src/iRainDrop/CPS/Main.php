<?php

namespace iRainDrop\CPS;

use iRainDrop\CPS\Commands\CPSCommand;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase{

    public static $cpsEnabled = [];
    public static $config;
    public static $lang;

    function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents(new EventListener, $this);
        $this->getServer()->getCommandMap()->register("CPS", new CPSCommand ($this));
        $this->saveresource("config.yml");
        $this->saveresource("lang.yml");
        self::$config = new Config($this->getDataFolder() . "config.yml");
        self::$lang = new Config($this->getDataFolder() . "lang.yml");
    }
}
