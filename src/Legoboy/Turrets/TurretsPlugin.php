<?php

namespace Legoboy\Turrets;

use Legoboy\Turrets\entity\EntityTurret;
use Legoboy\Turrets\entity\Minecart;
use Legoboy\Turrets\entity\TurretProjectile;
use Legoboy\Turrets\persistence\TurretDatabase;
use Legoboy\Turrets\persistence\YAMLTurretDatabase;
use Legoboy\Turrets\targeting\MobAssessor;
use Legoboy\Turrets\targeting\TargetAssessor;
use Legoboy\Turrets\upgrade\UpgradeLadder;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class TurretsPlugin extends PluginBase{

	const TURRET_DB_FILENAME = "turrets.yml";
	const POST_MATERIAL = Block::FENCE;

	const PERM_TURRET_CREATE = "turrets.create";

	const PERM_TURRET_DESTROY = "turrets.destroy";
	const PERM_ADMIN = "turrets.admin";

	private static $instance = null;

	/** @var UpgradeLadder */
	private $upgradeLadder = null;

	/** @var TurretDatabase */
	private $turretDatabase;

	/** @var TargetAssessor[] */
	private $targetAssessors = [];

	/** @var Turret[] */
	private $turrets = [];

	/**
	 * @return TurretsPlugin|null
	 */
	public static function getInstance(){
		return self::$instance;
	}

	public function onLoad(){
		if(self::$instance === null){
			self::$instance = $this;
		}
		$this->turretDatabase = new YAMLTurretDatabase($this->getDataFolder() . "turrets.yml", $this);
	}

	public function onEnable(){
		Entity::registerEntity(Minecart::class);
		Entity::registerEntity(EntityTurret::class, true);
		Entity::registerEntity(TurretProjectile::class, true);

		$this->upgradeLadder = new UpgradeLadder();

		$this->targetAssessors[] = new MobAssessor();

		$logger = $this->getLogger();
		$server = $this->getServer();
		$pluginManager = $server->getPluginManager();

		$this->saveDefaultConfig();
		$config = $this->getConfig();

		$this->upgradeLadder->loadUpgradeTiers($config, $logger);
		$logger->info("Upgrade tiers loaded.");

		$pluginManager->registerEvents(new TurretsListener($this), $this);

		$server->getCommandMap()->register('turrets', new TurretsCommand($this));

		try{
			$this->loadTurrets();
			$logger->info("Turrets loaded and spawned.");
		}catch(\Exception $e){
			$logger->error("Failed to load turrets. Error: " . $e->getMessage());
		}

		$logger->alert("Total number of turrets: " . count($this->turrets));
	}

	public function onDisable(){
		$logger = $this->getLogger();
		try{
			$this->saveTurrets();
			$logger->info("Saved all the turrets.");
		}catch(\Exception $e){
			$logger->error("Failed to save turrets. Error: " . $e->getMessage());
		}
	}

	public function getUpgradeLadder() : UpgradeLadder{
		return $this->upgradeLadder;
	}

	public function getTurretDatabase() : TurretDatabase{
		return $this->turretDatabase;
	}

	/**
	 * @return TargetAssessor[]
	 */
	public function getTargetAssessors() : array{
		return $this->targetAssessors;
	}

	/**
	 * @return Turret[]
	 */
	public function getTurrets() : array{
		return $this->turrets;
	}

	/**
	 * @param Position $postLocation
	 * @return Turret|null
	 */
	public function getTurret(Position $postLocation){
		return $this->turrets[Turret::hash($postLocation)] ?? null;
	}

	/**
	 * @param string $locationHash
	 * @return Turret|null
	 */
	public function getTurretFromHash($locationHash){
		return $this->turrets[$locationHash] ?? null;
	}

	public function addTurret(Position $position, string $player){
		$hash = Turret::hash($position);

		if(!isset($this->turrets[$hash])){
			$this->turrets[$hash] = $turret = new Turret($position, $player, $this);
			$turret->createEntity();
		}
	}

	public function removeTurret(Turret $turret, bool $despawn = true){
		if($despawn) $turret->despawn();
		unset($this->turrets[Turret::hash($turret->getPosition())]);
	}

	public function canBuildTurret(Vector3 $position) : bool{
		return !isset($this->turrets[Turret::hash($position)]);
	}

	public function saveTurrets(){
		$this->turretDatabase->saveTurrets($this->turrets);
	}

	public function loadTurrets(){
		$dbTurrets = $this->turretDatabase->loadTurrets();
		if($dbTurrets === null){
			return;
		}
		/** @var Turret $turret */
		foreach($dbTurrets as $turret){
			$hash = Turret::hash($turret->getPosition());
			if(!isset($this->turrets[$hash])){
				$this->turrets[$hash] = $turret;
			}
		}
	}

	public function despawnAndSaveTurrets(){
		$this->turretDatabase->saveTurrets($this->turrets);
		foreach($this->turrets as $hash => $turret){
			$turret->despawn();
			unset($this->turrets[$hash]);
		}
	}

	public function notifyPlayer(Player $player, $messageType){
		$message = self::getMessage($messageType);
		if($message === null){
			$message = $messageType;
		}
		$player->sendMessage("[Turrets] $message");
	}

	public static function getMessage($messageType){
		if(!is_numeric($messageType)){
			return null;
		}
		switch($messageType){
			case TurretsMessage::NO_CREATE_PERM:
				return "You do not have permission to create turrets.";
			case TurretsMessage::NO_DESTROY_PERM:
				return "You do not have permission to destroy turrets.";
			case TurretsMessage::TURRET_CANNOT_BUILD:
				return "You cannot build a turret here!";
			case TurretsMessage::TURRET_CREATED:
				return "Turret created!";
			case TurretsMessage::TURRET_DESTROYED:
				return "Turret destroyed!";
			case TurretsMessage::TURRET_UPGRADED:
				return "Turret upgraded!";
			case TurretsMessage::TURRET_DOWNGRADED:
				return "Turret downgraded!";
		}
		return null;
	}
}