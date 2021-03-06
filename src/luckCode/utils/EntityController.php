<?php

declare(strict_types=1);

namespace luckCode\utils;

use luckCode\data\types\YamlData;
use luckCode\LuckCodePlugin;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use function array_walk;

final class EntityController {

    /** @var Player[] $fastKill */
    private static $fastKill = [];

    /**
     * @param Player $player
     */
    public static function addFastKill(Player $player) {
        self::$fastKill[spl_object_hash($player)] = $player;
    }

    /**
     * @param Player $player
     */
    public static function removeFastKill(Player $player) {
        unset(self::$fastKill[spl_object_hash($player)]);
    }

    /** @return Player[] */
    public static function getAllInFastKill(): array {
        return self::$fastKill;
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public static function onUpdate(Entity $entity): bool {
        $rangeMax = self::getData()->get('range_view');
        $level = $entity->level;
        if ($entity->isAlive()) {
            $players = $level->getPlayers();
            array_walk($players, function (Player $p) use ($entity, $rangeMax) {
                if ($p->distance($entity) > $rangeMax && isset($entity->getViewers()[$p->getLoaderId()])) {
                    $entity->despawnFrom($p);
                } else if ($p->distance($entity) <= $rangeMax && !isset($entity->getViewers()[$p->getLoaderId()])) {
                    $entity->spawnTo($p);
                }
            });
            return false;
        }
        return true;
    }

    /**
     * @return YamlData
     */
    public static function getData(): YamlData {
        return LuckCodePlugin::getInstance()->getDataManager()->get('entities');
    }

    /**
     * @param EntityDamageEvent $e
     * @return bool
     */
    public static function onAttack(EntityDamageEvent $e): bool {
        $entity = $e->getEntity();
        if ($e instanceof EntityDamageByEntityEvent) {
            $damager = $e->getDamager();
            if (
                $damager instanceof Player &&
                self::inFastKill($damager) &&
                $damager->hasPermission(LuckCodePlugin::ADMIN_PERMISSION) &&
                $damager->getInventory()->getItemInHand()->getId() == Item::GOLDEN_SWORD
            ) {
                $entity->setHealth(0);
                $entity->close();
                $damager->sendMessage(LuckCodePlugin::PREFIX . '§aEntidade #' . $entity->getId() . ' removida!');
                return false;
            }
        }
        return true;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public static function inFastKill(Player $player): bool {
        return array_key_exists(spl_object_hash($player), self::$fastKill);
    }
}
