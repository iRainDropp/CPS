<?php


namespace iRainDrop\CPS;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use iRainDrop\CPS\DiscordWebhookAPI\task\DiscordWebhookSendTask;
use iRainDrop\CPS\DiscordWebhookAPI\Embed;
use iRainDrop\CPS\DiscordWebhookAPI\Webhook;
use iRainDrop\CPS\DiscordWebhookAPI\Message;

class CPSTask extends Task
{
    private $plugin;
    private $delay = 0;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;

    }

    public function onRun(int $currentTick)
    {
        $this->updateHud();
        foreach($this->plugin->tip as $playerName => $cps){
            if($cps >= 1)
            {
                $this->plugin->tip[$playerName] = 0;
            }
            if($this->plugin->realCPS[$playerName] >= 1)
            {
                $this->plugin->realCPS[$playerName] = 0;
            }
        }
    }

    public function updateHud()
    {
        foreach($this->plugin->tip as $playerName => $cps){
            if($this->plugin->Enabled[$playerName] === true)
            {
                $p = $this->plugin->getServer()->getPlayer($playerName);
                $p->sendTip($this->plugin->CPSMessage . $cps);
            }

            if($this->delay <time()){
                if($this->plugin->realCPS[$playerName] >= $this->plugin->cpsAmount){
                    foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
                        if($player->hasPermission("cps.alerts")){
                            $message = str_replace("{playername}", $playerName, $this->plugin->cpsAlerts);
                            $message = str_replace("{cps}", $this->plugin->realCPS[$playerName], $message);
                            $message = str_replace($this->plugin->CPSLimit, $this->plugin->CPSLimit . "+", $message);
                            $player->sendMessage($message);

                            if($this->plugin->enabledWebhook === true){
                                $webhook = new Webhook($this->plugin->webhookURL);
                                $msg = new Message();
                                $msg->setUsername($this->plugin->displayName);
                                if(isset($this->plugin->webhookAvatar)){
                                    $msg->setAvatarURL($this->plugin->webhookAvatar);
                                }
                                $msg->setContent("");

                                $embed = new Embed();
                                $embed->setTitle($this->plugin->webhookTitle);
                                $embedMsg = str_replace("{playername}", $playerName, $this->plugin->webhookMessage);
                                $embedMsg = str_replace("{cps}", $this->plugin->realCPS[$playerName], $embedMsg);
                                $embedMsg = str_replace($this->plugin->CPSLimit, $this->plugin->CPSLimit . "+", $embedMsg);
                                $embed->setDescription($embedMsg);
                                $embed->setFooter($this->plugin->webhookFooter);
                                $embed->setColor($this->plugin->webhookColor);
                                $msg->addEmbed($embed);

                                $webhook->send($msg);
                            }
                        }
                        $this->delay = time() + $this->plugin->alertCooldown;
                    }
                }
            }
        }
    }
}
