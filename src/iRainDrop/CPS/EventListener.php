<?php

namespace iRainDrop\CPS;

use iRainDrop\CPS\DiscordWebhookAPI\CortexPE\DiscordWebhookAPI\Embed;
use iRainDrop\CPS\DiscordWebhookAPI\CortexPE\DiscordWebhookAPI\Message;
use iRainDrop\CPS\DiscordWebhookAPI\CortexPE\DiscordWebhookAPI\Webhook;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class EventListener implements Listener
{
    public $cps = [];
    private $cooldown = [];

    public function onDisconnect(PlayerQuitEvent $event){
        unset($this->cps[$event->getPlayer()->getName()]);
    }

    public function onConnect(PlayerJoinEvent $event){
        Main::$cpsEnabled[$event->getPlayer()->getName()] = Main::$config->getNested("CPS.On Join");
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event){
        $packet = $event->getPacket();

        if($packet instanceof LevelSoundEventPacket){
            if($packet->sound === LevelSoundEvent::ATTACK_NODAMAGE){
                $this->addCPS($event->getOrigin()->getPlayer());
                if(Main::$cpsEnabled[$event->getOrigin()->getPlayer()->getName()] === true){
                    $popup = str_replace("{cps}", $this->getCPS($event->getOrigin()->getPlayer()), Main::$config->getNested("CPS.Popup"));
                    $event->getOrigin()->getPlayer()->sendPopup($popup);
                }
                if($this->getCPS($event->getOrigin()->getPlayer()) >= Main::$config->getNested("CPS.Trigger Amount")){
                    $players = server::getInstance()->getOnlinePlayers();
                    if(!$this->hasCooldown($event->getOrigin()->getPlayer())){
                        if(Main::$config->getNested("Discord Webhook.Enabled") === true){
                            $webhook = new Webhook(Main::$config->getNested("Discord Webhook.Webhook URL"));
                            $msg = new Message();
                            $msg->setUsername(Main::$config->getNested("Discord Webhook.Display Name"));
                            $webhookAvatar = Main::$config->getNested("Discord Webhook.Avatar");
                            if(isset($webhookAvatar)){
                                $msg->setAvatarURL($webhookAvatar);
                            }
                            $msg->setContent("");

                            $embed = new Embed();
                            $embed->setTitle(Main::$config->getNested("Discord Webhook.Title"));
                            $description = str_replace("{player}", $event->getOrigin()->getPlayer()->getName(), Main::$config->getNested("Discord Webhook.Description"));
                            $description = str_replace("{cps}", $this->getCPS($event->getOrigin()->getPlayer()), $description);
                            $embed->setDescription($description);
                            $embed->setFooter(Main::$config->getNested("Discord Webhook.Footer"));
                            $embed->setColor(Main::$config->getNested("Discord Webhook.Color"));
                            $msg->addEmbed($embed);

                            $webhook->send($msg);
                        }
                        $this->updateCooldown($event->getOrigin()->getPlayer());
                        foreach($players as $playerName){
                            $offender = $event->getOrigin()->getPlayer()->getName();
                            if($playerName->hasPermission(Main::$config->getNested("CPS.Alerts Permission"))){
                                $cpsAlerts = str_replace("{player}", $event->getOrigin()->getPlayer()->getName(), Main::$config->getNested("CPS.Alerts"));
                                $cpsAlerts = str_replace("{cps}", $this->getCPS($event->getOrigin()->getPlayer()), $cpsAlerts);
                                $playerName->sendMessage($cpsAlerts);
                            }
                        }
                    }
                }
            }
        }
        if($packet instanceof InventoryTransactionPacket){
            if($packet->trData instanceof UseItemOnEntityTransactionData){
                $this->addCPS($event->getOrigin()->getPlayer());
                if(isset(Main::$cpsEnabled[$event->getOrigin()->getPlayer()->getName()])){
                if(Main::$cpsEnabled[$event->getOrigin()->getPlayer()->getName()] === true){
                    $popup = str_replace("{cps}", $this->getCPS($event->getOrigin()->getPlayer()), Main::$config->getNested("CPS.Popup"));
                    $event->getOrigin()->getPlayer()->sendPopup($popup);
                    }
                }
                if($this->getCPS($event->getOrigin()->getPlayer()) >= Main::$config->getNested("CPS.Trigger Amount")){
                    $players = server::getInstance()->getOnlinePlayers();

                    if(!$this->hasCooldown($event->getOrigin()->getPlayer())){
                        if(Main::$config->getNested("Discord Webhook.Enabled") === true){
                            $webhook = new Webhook(Main::$config->getNested("Discord Webhook.Webhook URL"));
                            $msg = new Message();
                            $msg->setUsername($event->getOrigin()->getPlayer()->getName());
                            $webhookAvatar = Main::$config->getNested("Discord Webhook.Avatar");
                            if(isset($webhookAvatar)){
                                $msg->setAvatarURL($webhookAvatar);
                            }
                            $msg->setContent("");

                            $embed = new Embed();
                            $embed->setTitle(Main::$config->getNested("Discord Webhook.Title"));
                            $description = str_replace("{player}", $event->getOrigin()->getPlayer()->getName(), Main::$config->getNested("Discord Webhook.Description"));
                            $description = str_replace("{cps}", $this->getCPS($event->getOrigin()->getPlayer()), $description);
                            $embed->setDescription($description);
                            $embed->setFooter(Main::$config->getNested("Discord Webhook.Footer"));
                            $embed->setColor(Main::$config->getNested("Discord Webhook.Color"));
                            $msg->addEmbed($embed);

                            $webhook->send($msg);
                        }
                        $this->updateCooldown($event->getOrigin()->getPlayer());
                        foreach($players as $playerName){
                            $offender = $event->getOrigin()->getPlayer()->getName();
                            if($playerName->hasPermission(Main::$config->getNested("CPS.Alerts Permission"))){
                                $cpsAlerts = str_replace("{player}", $event->getOrigin()->getPlayer()->getName(), Main::$config->getNested("CPS.Alerts"));
                                $cpsAlerts = str_replace("{cps}", $this->getCPS($event->getOrigin()->getPlayer()), $cpsAlerts);
                                $playerName->sendMessage($cpsAlerts);
                            }
                        }
                    }
                }
            }
            if($this->getCPS($event->getOrigin()->getPlayer()) > Main::$config->getNested("CPS.Limit")){
                $event->cancel();
            }
        }
    }

    //public function onPlayerInteract(PlayerInteractEvent $event){
        //if($event->getAction() == PlayerInteractEvent::LEFT_CLICK_BLOCK){
            //$this->addCPS($event->getPlayer());
            //if(Main::$cpsEnabled[$event->getPlayer()->getName()] === true){
                //$event->getPlayer()->sendPopup("CPS: " . $this->getCPS($event->getPlayer()));
            //}
            //if($this->getCPS($event->getPlayer()) >= 15){
                //$players = server::getInstance()->getOnlinePlayers();
                //foreach($players as $playerName){
                    //$offender = $event->getPlayer()->getName();
                    //if($playerName->getName() === "RainDropTY"){
                        //$playerName->sendMessage(TextFormat::RED . $offender . " is currently clicking " . $this->getCPS($event->getPlayer()) . " CPS.");
                    //}
                //}
            //}
        //}
    //}

    public function hasCooldown(Player $player): bool{
        return isset($this->cooldown[$player->getName()]) && $this->cooldown[$player->getName()] > time();
    }

    public function updateCooldown(Player $player): void{
        $this->cooldown[$player->getName()] = time() + Main::$config->getNested("CPS.Alert Cooldown");
    }

    public function addCPS(Player $player): void{
        $time = microtime(true);
        $this->cps[$player->getName()][] = $time;
    }

    public function getCPS(Player $player): int{
        $time = microtime(true);
        return count(array_filter($this->cps[$player->getName()] ?? [], static function(float $t) use ($time):bool{
            return ($time - $t) <= 1;
        }));
    }
}
