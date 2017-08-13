<?php

declare(strict_types=1);

namespace Legoboy\Turrets;

use Legoboy\Turrets\entity\EntityTurret;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;

class TurretsCommand extends Command implements PluginIdentifiableCommand{

	/** @var TurretsPlugin */
	private $plugin;

	public function __construct(TurretsPlugin $plugin){
		parent::__construct('turrets', 'Main command for turrets', '/turrets [save]', []);
		$this->plugin = $plugin;
	}

	public function getPlugin(){
		return $this->plugin;
	}

	public function execute(CommandSender $sender, $commandLabel, array $args) : bool{
		if(count($args) > 0){
			$subCommand = strtolower($args[0]);
			if($subCommand === 'save'){
				try{
					$this->plugin->saveTurrets();
					$sender->sendMessage("Turrets saved to database.");
				}catch(\Exception $e){
					$this->plugin->getLogger()->info("Failed to save turrets. Error: {$e->getMessage()}");
					$sender->sendMessage("Error saving turrets.");
				}
				return true;
			}elseif($subCommand === 'despawn'){
				if(!($sender instanceof Player)){
					$sender->sendMessage("Please run this command in-game.");
					return true;
				}
				try{
					$levelEntities = $sender->getLevel()->getEntities();
					$i = 0;
					foreach($levelEntities as $entity){
						if($entity instanceof EntityTurret){
							$entity->close();
							++$i;
						}
					}
					if($i > 0){
						$sender->sendMessage("$i turret(s) were despawned.");
					}else{
						$sender->sendMessage("No turrets were despawned!");
					}
				}catch(\Exception $e){
					$this->plugin->getLogger()->info("Failed to despawn turrets. Error: {$e->getMessage()}");
					$sender->sendMessage("Error despawning turrets.");
				}
				return true;
			}
			$sender->sendMessage("$subCommand is not a valid Turrets subcommand.");
			return true;
		}
		$sender->sendMessage("Total number of turrets: " . count($this->plugin->getTurrets()));
		return true;
	}
}
