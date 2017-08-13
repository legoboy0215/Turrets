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
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\ItemIds;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class TurretsListener implements Listener{
	/** @var TurretsPlugin */
	private $plugin;

	public function __construct(TurretsPlugin $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @param PlayerInteractEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onPlayerInteract(PlayerInteractEvent $event){
		if(($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) && ($event->getFace() === Vector3::SIDE_UP)){
			$clickedBlock = $event->getBlock();
			$itemInHand = $event->getItem();
			$player = $event->getPlayer();

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
			if($entity->namedtag->offsetExists('Hash')){
				if(($turret = $this->plugin->getTurretFromHash($entity->namedtag->offsetGet('Hash'))) !== null){
					$turret->setEntity($entity->getId());
					$entity->setTurret($turret);
				}
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
		if($event instanceof EntityDamageByChildEntityEvent){
			$arrow = $event->getChild();
			$damager = $event->getDamager();
			if($arrow instanceof TurretProjectile && $damager instanceof EntityTurret){
				$event->getEntity()->sendPopup(TextFormat::AQUA . 'Hit!');
			}
			return;
		}
		if(!($event instanceof EntityDamageByEntityEvent)){
			return;
		}
		$entity = $event->getEntity();

		if($entity instanceof Minecart && $entity instanceof EntityTurret){
			$turret = $entity->getTurret();

			if(isset($this->plugin->getTurrets()[$turret->getHash()])){
				$attacker = $event->getDamager();
				if(($attacker instanceof Player)){
					$hasPermission = $attacker->hasPermission("turrets.destroy");
					if(!$hasPermission){
						$this->plugin->notifyPlayer($attacker, TurretsMessage::NO_DESTROY_PERM);
						$event->setDamage(0);
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
			$postLocation = $block->asPosition();
			$turret = $this->plugin->getTurret($postLocation);

			if($turret !== null){
				$hasPermission = $player->hasPermission('turrets.destroy');
				if(!$hasPermission){
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

		$postLocation = Position::fromObject($block->add(0, 1, 0), $block->getLevel());
		$turret = $this->plugin->getTurret($postLocation);

		if($turret instanceof Turret){
			$turret->updateUpgradeTier();
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
			$newTier = $turret->updateUpgradeTierId($block->getId());

			echo "Upgrading tier...\n";

			if($newTier !== $prevTier){
				$this->plugin->notifyPlayer($player, TurretsMessage::TURRET_UPGRADED);
				$this->plugin->notifyPlayer($player, "Firing interval (lower is faster): " . TextFormat::AQUA . $newTier->getFiringInterval());
				$this->plugin->notifyPlayer($player, "Range: " . TextFormat::AQUA . $newTier->getRange());
				$this->plugin->notifyPlayer($player, "Accuracy (lower is more accurate): " . TextFormat::AQUA . $newTier->getAccuracy());
			}
		}
	}
}