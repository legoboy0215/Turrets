<?php

namespace Legoboy\Turrets\upgrade;

use Legoboy\Turrets\entity\EntityTurret;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\plugin\PluginLogger;
use pocketmine\utils\Config;

class UpgradeLadder{

	const TIERS_PATH = "tiers";
	const DEFAULT_TIER_NAME = "default";
	const FIRING_INTERVAL_PATH = "firingInterval";
	const RANGE_PATH = "range";
	const ACCURACY_PATH = "accuracy";

	private $upgradeTiers = [];

	private $defaultUpgradeTier = null;

	public function __construct(){

	}

	public function getUpgradeTier(int $blockId) : UpgradeTier{
		return $this->upgradeTiers[$blockId] ?? $this->defaultUpgradeTier;
	}

	public function loadUpgradeTiers(Config $config, PluginLogger $logger){
		$tierNodes = $config->get('tiers', []);

		foreach($tierNodes as $tierKey => $tierNode){
			$block = null;
			$blockId = null;
			if($tierKey !== "default"){
				if(is_numeric($tierKey)){
					$blockId = (int) $tierKey;
					$block = Block::get((int) $tierKey);
				}elseif(is_string($tierKey)){
					if(defined(BlockIds::class . '::' . strtoupper($tierKey))){
						$blockId = (int) constant(BlockIds::class . '::' . strtoupper($tierKey));
						$block = Block::get($blockId);
					}
				}

				if(($block === null) || ($block->getId() === Block::AIR)){
					$logger->warning("Invalid tier $tierKey, must be the name of a block. Skipping.");
					continue;
				}
			}
			$firingInterval = $tierNode["firingInterval"] ?? 20;
			if($firingInterval < 1 || ($firingInterval % EntityTurret::TASK_RUN_INTERVAL) !== 0){
				$logger->warning("Invalid firing interval $firingInterval, should be at least one and a multiple of " . EntityTurret::TASK_RUN_INTERVAL . ". Using default value.");
				$firingInterval = EntityTurret::TASK_RUN_INTERVAL * 4;
			}

			$range = $tierNode["range"] ?? 20.0;
			if($range <= 0.0){
				$logger->warning("Invalid range $range, must be positive. Using default value.");
				$range = 20.0;
			}

			$accuracy = $tierNode["accuracy"] ?? 1.0;
			if($accuracy < 0.0){
				$logger->warning("Invalid accuracy $accuracy, must be at least 0. Using default value.");
				$accuracy = 1.0;
			}

			$upgradeTier = new UpgradeTier($firingInterval, $range, $accuracy);

			if($block !== null && $blockId !== null){
				$this->upgradeTiers[(int) $blockId] = $upgradeTier;
	  		}else{
				$this->defaultUpgradeTier = $upgradeTier;
			}
		}

		if($this->defaultUpgradeTier === null){
			$logger->warning("No default upgrade tier, creating one.");
			$this->defaultUpgradeTier = new UpgradeTier(40, 10.0, 3.0);
		}
		var_dump($this->upgradeTiers);
	}
}
