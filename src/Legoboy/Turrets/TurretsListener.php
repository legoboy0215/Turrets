<?php

declare(strict_types=1);

namespace Legoboy\Turrets;

use Legoboy\Turrets\entity\EntityTurret;
use Legoboy\Turrets\entity\Minecart;
use Legoboy\Turrets\entity\TurretProjectile;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
//use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\EntitySpawnEvent;
//use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\ItemIds;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class TurretsListener implements Listener{

	/** @var TurretsPlugin */
	private $plugin;

	/** @var int[] */
	private $interactTimes = [];

	public function __construct(TurretsPlugin $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @param PlayerInteractEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled true
	 * @throws \InvalidStateException
	 */
	public function onPlayerInteract(PlayerInteractEvent $event){
		if(($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) && ($event->getFace() === Vector3::SIDE_UP)){
			$player = $event->getPlayer();

			if (isset($this->interactTimes[$player->getId()]) && (time() - $this->interactTimes[$player->getId()]) < 1) {
				return;
			} else {
				$this->interactTimes[$player->getId()] = time();
			}
			$clickedBlock = $event->getBlock();
			$itemInHand = $event->getItem();

			if(($clickedBlock->getId() === TurretsPlugin::POST_MATERIAL) && ($itemInHand->getId() === ItemIds::MINECART)){
				$hasPermission = $player->hasPermission('turrets.create') || $player->isOp();
				if(!$hasPermission){
					$this->plugin->notifyPlayer($player, TurretsMessage::NO_CREATE_PERM);
					return;
				}
				if($this->plugin->canBuildTurret($clickedBlock)){
					$this->plugin->addTurret($clickedBlock, $player->getName());
					$this->plugin->notifyPlayer($player, TurretsMessage::TURRET_CREATED);
				}else{
					$this->plugin->notifyPlayer($player, TurretsMessage::TURRET_CANNOT_BUILD);
				}
			}
		}
	}

	public function onSpawn(EntitySpawnEvent $event){
		$entity = $event->getEntity();
		if($entity instanceof EntityTurret){
			var_dump('Turret spawned!');
			if(($turret = $this->plugin->getTurretFromHash($entity->getHash())) !== null){
				$turret->setEntity($entity->getId());
				$entity->setTurret($turret);
			}
		}
	}

	/*public function onDespawn(EntityDespawnEvent $event){
		$entity = $event->getEntity();
		if($entity instanceof EntityTurret){
			var_dump('Turret despawned!');
			if($entity->namedtag->offsetExists('Hash')){
				if(($turret = $this->plugin->getTurretFromHash($entity->namedtag->offsetGet('Hash'))) !== null){
					$turret->remove(false);
					var_dump('Turret removed due to despawn.');
				}
			}
		}
	}*/

	/**
	 * @param EntityDamageEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onVehicleDestroy(EntityDamageEvent $event){
		$entity = $event->getEntity();
		if($event instanceof EntityDamageByChildEntityEvent){
			$arrow = $event->getChild();
			$damager = $event->getDamager();
			if($arrow instanceof TurretProjectile && $damager instanceof EntityTurret && $entity instanceof Player){
				$entity->sendPopup(TextFormat::AQUA . 'Hit!');
			}
			return;
		}
		if(!($event instanceof EntityDamageByEntityEvent)){
			return;
		}

		if($entity instanceof Minecart && $entity instanceof EntityTurret){
			$turret = $entity->getTurret();
			if ($turret === null) {
				$entity->flagForDespawn();
				$entity->despawnFromAll();
				return;
			}

			if(isset($this->plugin->getTurrets()[$turret->getHash()])){
				$attacker = $event->getDamager();
				if(($attacker instanceof Player)){
					$hasPermission = $attacker->hasPermission("turrets.destroy");
					if(!$hasPermission){
						$this->plugin->notifyPlayer($attacker, TurretsMessage::NO_DESTROY_PERM);
						$event->setBaseDamage(0);
						$event->setCancelled(true);
						return;
					}
					$turret->remove();

					$this->plugin->notifyPlayer($attacker, TurretsMessage::TURRET_DESTROYED);
				}else{
					$turret->remove();
				}
			}
		}
	}

	/**
	 * @param BlockBreakEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onBlockBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$blockId = $block->getId();

		if($blockId === TurretsPlugin::POST_MATERIAL){
			$turret = $this->plugin->getTurret($block);

			if($turret !== null){
				if(!$player->hasPermission('turrets.destroy')){
					$this->plugin->notifyPlayer($player, TurretsMessage::NO_DESTROY_PERM);
					$event->setCancelled(true);
					return;
				}

				$this->plugin->removeTurret($turret);

				$this->plugin->notifyPlayer($player, TurretsMessage::TURRET_DESTROYED);
			}
		}
	}

	/**
	 * @param BlockBreakEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function afterBlockBreak(BlockBreakEvent $event){
		$block = $event->getBlock();
		$pos = Position::fromObject($block->add(0, 1, 0), $block->getLevel());
		if(($turret = $this->plugin->getTurret($pos)) instanceof Turret){
			$prevTier = $turret->getUpgradeTier();
			$newTier = $turret->updateUpgradeTier(0);

			if($newTier !== $prevTier){
				$player = $event->getPlayer();
				$this->plugin->notifyPlayer($player, TurretsMessage::TURRET_DOWNGRADED);
				$this->plugin->notifyPlayer($player, "Firing interval: " . TextFormat::AQUA . $newTier->getFiringInterval());
				$this->plugin->notifyPlayer($player, "Range: " . TextFormat::AQUA . $newTier->getRange());
				$this->plugin->notifyPlayer($player, "Accuracy: " . TextFormat::AQUA . $newTier->getAccuracy());
			}
		}
	}

	/**
	 * @param BlockPlaceEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onBlockPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();

		$postLocation = Position::fromObject($block->add(0, 1, 0), $block->getLevel());
		$turret = $this->plugin->getTurret($postLocation);

		if($turret instanceof Turret){
			$prevTier = $turret->getUpgradeTier();
			$newTier = $turret->updateUpgradeTier($block->getId());

			if($newTier !== $prevTier){
				$this->plugin->notifyPlayer($player, TurretsMessage::TURRET_UPGRADED);
				$this->plugin->notifyPlayer($player, "Firing interval: " . TextFormat::AQUA . $newTier->getFiringInterval());
				$this->plugin->notifyPlayer($player, "Range: " . TextFormat::AQUA . $newTier->getRange());
				$this->plugin->notifyPlayer($player, "Accuracy: " . TextFormat::AQUA . $newTier->getAccuracy());
			}
		}
	}

	public function onLeave(PlayerQuitEvent $event) {
		$playerId = $event->getPlayer()->getId();
		if (isset($this->interactTimes[$playerId])) {
			unset($this->interactTimes[$playerId]);
			echo "Unset $playerId!\n";
		}
	}
}