<?php


namespace Gumbratt;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\inventory\ChestInventory;
use pocketmine\item\Item;
use pocketmine\tile\Chest;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\inventory\Inventory;
use pocketmine\level\particle\LavaParticle;
use pocketmine\level\sound\PopSound;

class Treasure extends PluginBase implements Listener{
	
	private $status;
	private $stop;
	private $chests;
	
	public function onEnable(){
		$this->chests = array();
		$this->saveDefaultConfig();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		
		$this->getLogger()->info($this->getMessage('enable'));
	}
	
	public function onDisable(){
				$this->getLogger()->info($this->getMessage('disable'));
	}

	public function getChance(){
		$mC = 1000 - $this->getConfig()->get('mega-chance');
		$nC = $this->getConfig()->get('chance');
		$rC = mt_rand(0, 1000);
		if($rC <= $nC){
			$this->getLogger()->info("$rC \<\= $nC");
			return 1;
		}elseif($mC < $rC){
			$this->getLogger()->info("$mC < $rC");
			return 2;
		}else{
			return 0;
			}
		}
	
	public function spawnChest($player, $type){
		$x = $player->getFloorX();
		$y = $player->getFloorY();
		$z = $player->getFloorZ();
		$pos = new Vector3($x, $y, $z);
		$posParticle = new Vector3($x, $y + 1, $z);
		$sound = new PopSound($pos);
		$particle = new LavaParticle($posParticle, 9999999999999999999999999999999999999999999);
		$chestP = "$x:$y:$z";
		$this->chests["$chestP"] = $type;
		$chest = Block::get(54)->getId();
		$chest = new Block($chest);
		// =============================================================
		if($chest instanceof Block){
		$player->getLevel()->setBlock($pos, $chest, true, false);
		$player->getLevel()->addSound($sound);
		for($i = 0; $i < 100; $i++){
		$player->getLevel()->addParticle($particle);
	}
		}
	}
	
	public function getMessage($message, $player = null){
		switch($message){ // Greate Idea xD 
			case 'enable':
			$msg = $this->getConfig()->get('enable');
			return $msg;
			break;
			case 'disable':
			$msg = $this->getConfig()->get('disable');
			return $msg;
			break;
			case 'chest-spawned':
			$msg = null;
			return $msg;
			break;
			case 'player-got-treasure':
			$msg = $this->getConfig()->get('player-got-treasure');
			$msg = str_replace('{PLAYER}', $player->getName(), $msg);
			return $msg;
			break;
			case "player-got-mega-treasure":
			$msg = $this->getConfig()->get('player-got-mega-treasure');
			$msg = str_replace('{PLAYER}', $player->getName(), $msg);
			return $msg;
			default:
			$msg = "Message not found";
			return $msg;
			break;
		}
	}
	
	public function onBlockBreak(BlockBreakEvent $event){
		$p = $event->getPlayer();
		$pN = $p->getName();
		$b = $event->getBlock();
		if($b->getid() === Block::STONE){
		$chance = $this->getChance();
		if($chance === 1){
			$this->spawnChest($p, 1);
	$this->getServer()->broadcastMessage($this->getMessage('player-got-treasure', $p));
	}elseif($chance === 2){
		$this->spawnChest($p, 2);
	$this->getServer()->broadcastMessage($this->getMessage('player-got-mega-treasure', $p));
	}
	}
	}
	
	private function createItems($inv){
		$this->stop = false;
		$bl = $inv->getHolder()->getBlock();
		$x = $bl->getFloorX();
		$y = $bl->getFloorY();
		$z = $bl->getFloorZ();
		$pos = new Vector3($x, $y, $z);
		$chestP = "$x:$y:$z";
		if($this->chests[$chestP] == 1){
	//		$this->getLogger()->info('Using normal items');
		$items = $this->getConfig()->get('items');
		}elseif($this->chests[$chestP] == 2){
			$items = $this->getConfig()->get('mega-items');
		//	$this->getLogger()->info('Using MEGA items');
			}else{
		//		$this->getLogger()->info('Non of above');
				$this->stop = true;
				}
			//	$this->getLogger()->info('Putting items...');
				if($this->chests[$chestP] == 0) return;
		for($c = 0; $this->stop === false; ++$c){
			if(isset($items[$c])){
				$it = explode("-",$items[$c]);
				$items[$c] = new Item($it[0], $it[1], $it[2]); 
			//	$this->getLogger()->info('Item created = Item - '. $it[0].'. Meta - '.$it[1].'. Ammount - '. $it[1].'.');
			//   $this->getLogger()->info('Item - '. $items[$c]);
				$this->stop = false;
			}else{
				$this->stop = true;
				return $items;
				}
		}
	}
	
	private function loadInventory(Inventory $inv) {
		if(!$this->isTreChe($inv)) return;
		
		$inv->clearAll();
		$items = $this->createItems($inv);
		$stop = false;
		for($i = 1; $stop === false; null){
			$c = rand(0, 20);
			if(isset($items[$c])){
				if($c === rand(0, 20) or $c === rand(0, 20) or $c === rand(0, 20)){
			//		$this->getLogger()->info('Breaked');
					$stop = true;
					break;
				}else{
					$inv->setItem($i, $items[$c]);
		//	$this->getLogger()->info('Item put '. $c);
			$i++;
			unset($c);
			}
		}
		
		}
		return true;
	}
	
	public function isTreChe(Inventory $inv) {
		if ($inv instanceof DoubleChestInventory) return false;
		if (!($inv instanceof ChestInventory)) return false;
		$tile = $inv->getHolder();
		if (!($tile instanceof Chest)) return false;
		$bl = $tile->getBlock();
		if ($bl->getId() != Block::CHEST) return false;
		$x = $bl->getFloorX();
		$y = $bl->getFloorY();
		$z = $bl->getFloorZ();
		$bpos = "$x:$y:$z";
		$chests = $this->chests;
		if(array_key_exists($bpos, $chests) == 2){
			return true;
	}elseif(array_key_exists($bpos, $chests) == 1){
		return true;
		}else{
		return false;
}
	}
	
	public function onInventoryOpenEvent(InventoryOpenEvent $ev) {
		if ($ev->isCancelled()) return;
		$inv = $ev->getInventory();
		if (!$this->isTreChe($inv)) return;
		$this->loadInventory($inv);
	}
	
	public function onInventoryCloseEvent(InventoryCloseEvent $ev){
		$inv = $ev->getInventory();
		if (!$this->isTreChe($inv)) return;
		$this->loadInventory($inv);
	//	$this->getLogger()->info('onInventoryOpen: Treasure chest = true. Deleting old pos');
	 	$this->deleteChest($inv);
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		switch($command->getName()){
			case "tclear":
			$this->chests = array();
			$level = $sender->getLevel();
			$entities = $level->getEntities();
			$tiles = $level->getTiles();
			$x = $sender->getFloorX();
			$z = $sender->getFloorZ();
			foreach($entities as $entity){
				$level->removeEntity($entity);
				$sender->sendMessage('['.$entity->getId().'] -  '. $entity->getFloorX().'.'.$entity->getFloorY().'.'.$entity->getFloorZ() .' deleted');
			}
			foreach($tiles as $tile){
				$level->removeTile($tile);
				if($tile instanceof Chest){
					$tile->getInventory()->clearAll();
				}
				$sender->sendMessage('['.$tile->getBlock()->getName().'] at '. $tile->getFloorX().'.'.$tile->getFloorY().'.'.$tile->getFloorZ() .' deleted');
			}
			$level->chunkHash($x, $z);
			$level->unloadChunk($x, $z, false);
			$level->clearCache();
			$level->loadChunk($x, $z, true);
			$level->regenerateChunk($x, $z);
			return true;	
			}
		}

		
	public function deleteChest(Inventory $inv){
		$bl = $inv->getHolder()->getBlock();
		$x = $bl->getFloorX();
		$y = $bl->getFloorY();
		$z = $bl->getFloorZ();
		unset($this->chests[$x.":".$y.":".$z]);
	//	$this->getLogger()->info('Chest deleted');
		}
}
