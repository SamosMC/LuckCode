<?php

declare(strict_types=1);

namespace luckCode\menu;

use function count;
use luckCode\LuckCodePlugin;
use luckCode\menu\interfaces\IMenu;
use luckCode\menu\manager\MenuController;
use luckCode\menu\tile\MenuChestTile;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\event\Listener;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\InventoryType;
use pocketmine\inventory\Transaction;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;

abstract class Menu extends ContainerInventory implements IMenu, Listener
{

    /** @var Position $position */
    protected $position;

    public function __construct(InventoryHolder $holder, InventoryType $type, array $items = [], $overrideSize = null, $overrideTitle = null)
    {
        parent::__construct($holder, $type, $items, $overrideSize, $overrideTitle);
        Server::getInstance()->getPluginManager()->registerEvents($this, LuckCodePlugin::getInstance());
    }

    /**
     * @param array $items
     */
    public function setItems(array $items)
    {
        $this->clearAll(false);
        foreach ($items as $k => $v) {
            $this->setItem($k, $v, false);
        }
        $this->sendContents($this->getViewers());
    }

    /**
     * @inheritDoc
     */
    public function sendBlock(Player $p, Block $block)
    {
        $this->position->level->sendBlocks([$p], [$block], UpdateBlockPacket::FLAG_ALL);
    }

    /**
     * @inheritDoc
     */
    public function makeTile(Position $pos, Player $p, string $name): MenuChestTile
    {
        $level = $pos->level;
        $c = new CompoundTag("", [
            new StringTag("id", Tile::CHEST),
            new IntTag("x", (int)$pos->x),
            new IntTag("y", (int)$pos->y),
            new IntTag("z", (int)$pos->z),
            new StringTag("CustomName", $name)
        ]);
        return Tile::createTile('MenuChestTile', $level->getChunk($pos->x >> 4, $pos->z >> 4), $c, $p);
    }

    /**
     * @param InventoryTransactionEvent $e
     * @priority HIGHEST
     */
    public function onTransaction(InventoryTransactionEvent $e) {
        $t = $e->getTransaction();
        $p = $t->getPlayer();
        foreach($t->getTransactions() as $a) {
            $inv = $a->getInventory();

            $item = $a->getChange()['out'] ?? $a->getChange()['in'];

            if($item != null && $inv === $this && $this->processClick($p, $item)) {
                $e->setCancelled(true);
                $this->fixFloatingInventory($p);
                break;
            }
        }
    }

    /** @param Player $p */
    private function fixFloatingInventory(Player $p) 
    {
        if(LuckCodePlugin::getInstance()->getDataManager()->get('menu')->get('fix_floating_inventory')) {
            $floatingInventory = $p->getFloatingInventory();
            $floatingContents = array_filter($floatingInventory->getContents(), function($item){
                return $item instanceof Item;
            });
            $floatingInventory->clearAll(false);

            if(count($floatingContents) > 0) {
                $inv = $p->getInventory();
                $inv->addItem(...$floatingContents);
                $inv->sendContents([$p]);
            }
        }
    }

    /** 
     * @param Player $who
     */
    public function onOpen(Player $who) 
    {
        parent::onOpen($who);
        MenuController::put($who, $this);
    }

    /**
     * @param Player $who
     */
    public function onClose(Player $who)
    {
        parent::onClose($who);
        if(!count($this->getViewers()) && $this->holder instanceof Chest) {
            $pair = $this->holder->getPair();
            $this->holder->close();
            if($pair != null) $pair->close();
        }
        MenuController::remove($who);
        $this->fixFloatingInventory($who);
    }
}