<?php


namespace CPS;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

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
                if($this->plugin->realCPS[$playerName] >= 15){
                    foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
                        if($player->hasPermission("cps.alerts")){
                            $message = str_replace("{playername}", $playerName, $this->plugin->cpsAlerts);
                            $message = str_replace("{cps}", $this->plugin->realCPS[$playerName], $message);
                            $message = str_replace("20", "20+", $message);
                            $player->sendMessage($message);
                        }
                        $this->delay = time() + $this->plugin->alertCooldown;
                    }
                }
            }
        }
    }
}
