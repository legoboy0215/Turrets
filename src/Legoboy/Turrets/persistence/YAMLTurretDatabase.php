<?php

namespace Legoboy\Turrets\persistence;

use Legoboy\Turrets\Turret;
use Legoboy\Turrets\TurretsPlugin;
use pocketmine\level\Position;
use pocketmine\utils\Config;

class YAMLTurretDatabase implements TurretDatabase{

	/** @var TurretsPlugin */
	private $plugin;

	/** @var Config */
	private $backing;

	public function __construct(string $filePath, TurretsPlugin $plugin){
		$this->plugin = $plugin;
		$this->backing = new Config($filePath, Config::YAML, ['turrets' => []]);
	}

	/**
	 * @return Turret[]
	 */
	public function loadTurrets() : array{
		$section = $this->backing->get('turrets', []);
		$turrets = [];
		foreach($section as $turretId => $data){
			$level = $data['level'];
			if(!$this->plugin->getServer()->isLevelLoaded($level)){
				if(!$this->plugin->getServer()->loadLevel($level)){
					continue;
				}else{
					$level = $this->plugin->getServer()->getLevelByName($level);
				}
			}else{
				$level = $this->plugin->getServer()->getLevelByName($level);
			}
			$position = new Position((int) $data['x'], (int) $data['y'], (int) $data['z'], $level);
			$ownerName = $data['owner'];
			$turret = new Turret($position, $ownerName, $this->plugin);
			$turrets[] = $turret;
		}

		return $turrets;
	}

	/**
	 * @param Turret[] $turrets
	 */
	public function saveTurrets(array $turrets){
		if(!$this->backing->exists('turrets')){
			$this->backing->set('turrets', []);
		}
		$section = [];
		$id = 0;
		foreach($turrets as $turret){
			$position = $turret->getPosition();
			$section["t$id"] = [
				'x' => $position->getX(),
				'y' => $position->getY(),
				'z' => $position->getZ(),
				'level' => $turret->getLevel()->getName(),
				'owner' => $turret->getOwnerName()
			];
			++$id;
		}
		$this->backing->set('turrets', $section);

		$this->backing->save();
	}
}
