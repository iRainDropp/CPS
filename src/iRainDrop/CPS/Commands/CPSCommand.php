<?php

namespace iRainDrop\CPS\Commands;

use iRainDrop\CPS\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginOwned;

class CPSCommand extends Command implements PluginOwned
{
    public function __construct(private Main $plugin) {
        parent::__construct("cps", "Enable or disable the CPS popup.");
    }
    
    public function getOwningPlugin(): Main
    { return $this->plugin; }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if($sender instanceof Player){
            $numargs = count($args);
            if($numargs < 1){
                if(Main::$cpsEnabled[$sender->getName()] === false){
                    Main::$cpsEnabled[$sender->getName()] = true;
                    $sender->sendMessage(Main::$lang->getNested("CPS.Enabled"));
                }
                elseif(Main::$cpsEnabled[$sender->getName()] === true){
                    Main::$cpsEnabled[$sender->getName()] = false;
                    $sender->sendMessage(Main::$lang->getNested("CPS.Disabled"));
                }
                return;
            }
            else{
                if(strtolower($args[0]) === "off" || strtolower($args[0]) === "disable"){
                    if(Main::$cpsEnabled[$sender->getName()] === true){
                        Main::$cpsEnabled[$sender->getName()] = false;
                        $sender->sendMessage(Main::$lang->getNested("CPS.Disabled"));
                    }
                    else{
                        $sender->sendMessage(Main::$lang->getNested("CPS.Already Disabled"));
                    }
                    return;
                }
                if(strtolower($args[0]) === "on" || strtolower($args[0]) === "enable"){
                    if(Main::$cpsEnabled[$sender->getName()] === false){
                        Main::$cpsEnabled[$sender->getName()] = true;
                        $sender->sendMessage(Main::$lang->getNested("CPS.Enabled"));
                    }
                    else{
                        $sender->sendMessage(Main::$lang->getNested("CPS.Already Enabled"));
                    }
                    return;
                }
                else{
                    $sender->sendMessage(Main::$lang->getNested("CPS.Usage"));
                }
            }
        }
        else{
            $sender->sendMessage(Main::$lang->getNested("CPS.Players Only"));
        }
    }
}
