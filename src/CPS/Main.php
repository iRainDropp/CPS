<?php

namespace CPS;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\Inventory;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;

class Main extends PluginBase implements Listener
{
    private $config;
    private $CPSLimit;
    private $EnabledMessage;
    private $DisabledMessage;
    public $cpsAlerts;
    public $Enabled = [];
    public $CPSMessage;
    public $alertCooldown;
    public $tip = [];
    public $realCPS = [];

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->startTask();
        $this->saveResource("config.yml");
        $this->config = new Config($this->getDataFolder(). "config.yml");
        $this->CPSMessage = $this->config->get("CPS Popup");
        $this->alertCooldown = $this->config->get("CPS Alert Cooldown");
        $this->EnabledMessage = $this->config->get("Enabled Popup");
        $this->DisabledMessage = $this->config->get("Disabled Popup");
        $this->cpsAlerts = $this->config->get("CPS Alerts");
        $this->CPSLimit = $this->config->get("CPS Limit");
        $this->getLogger()->info(TextFormat::GREEN . "CPS Successfully Enabled!");
    }

    public function onDisable()
    {
        $this->getLogger()->info(TextFormat::RED . "CPS Disabled!");
    }

    public function startTask()
    {
        $this->getScheduler()->scheduleRepeatingTask(new CPSTask($this), 20);
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $this->realCPS[$player->getName()] = 0;
        $this->tip[$player->getName()] = 0;
        $this->Enabled[$event->getPlayer()->getName()] = true;
    }

    public function onDisconnect(PlayerQuitEvent $event){
        unset($this->tip[$event->getPlayer()->getName()]);
        unset($this->realCPS[$event->getPlayer()->getName()]);
        unset($this->Enabled[$event->getPlayer()->getName()]);
    }

    public function onCommand(CommandSender $sender, Command $command, string $alias, array $args): bool
    {
        switch ($command->getName()) {
            case "cps":
                if(!isset($args[0])){
                    if($this->Enabled[$sender->getName()] === false){
                        $this->Enabled[$sender->getName()] = true;
                        $sender->sendMessage($this->EnabledMessage);
                    }
                    elseif($this->Enabled[$sender->getName()] === true){
                        $this->Enabled[$sender->getName()] = false;
                        $sender->sendMessage($this->DisabledMessage);
                    }
                }
                else{
                    if(strtolower($args[0]) === "on" or strtolower($args[0]) === "true" or strtolower($args[0]) === "enable"){
                        $this->Enabled[$sender->getName()] = true;
                        $sender->sendMessage($this->EnabledMessage);
                        return true;
                    }
                    if(strtolower($args[0]) === "off" or strtolower($args[0]) === "false" or strtolower($args[0]) === "disable"){
                        $this->Enabled[$sender->getName()] = false;
                        $sender->sendMessage($this->DisabledMessage);
                    }
                    else{
                        $sender->sendMessage(TextFormat::RED . "Usage: /cps <on/off>");
                    }
                }
        }
        return true;
    }

    public function onDataPacketRecieve(DataPacketReceiveEvent $event)
    {
        $packet = $event->getPacket();
        if ($packet instanceof LevelSoundEventPacket) {
            if ($packet->sound == 42) {
                $this->tip[$event->getPlayer()->getName()] += 1;
            }
        } elseif ($packet instanceof InventoryTransactionPacket) {
            if ($packet->trData instanceof UseItemOnEntityTransactionData) {
                $this->tip[$event->getPlayer()->getName()] += 1;
            }
        }
        if (isset($this->tip[$event->getPlayer()->getName()])) {
            $cps = $this->tip[$event->getPlayer()->getName()];
            if ($cps >= $this->CPSLimit) {
                $event->setCancelled();
            }
        }
        if ($packet instanceof LevelSoundEventPacket) {
            if ($packet->sound == 42) {
                $this->realCPS[$event->getPlayer()->getName()] += 1;
            }
        } elseif ($packet instanceof InventoryTransactionPacket) {
            if ($packet->trData instanceof UseItemOnEntityTransactionData) {
                $this->realCPS[$event->getPlayer()->getName()] += 1;
            }
        }
    }
}