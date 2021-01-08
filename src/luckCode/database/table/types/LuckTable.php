<?php

declare(strict_types=1);

namespace luckCode\database\table\types;

use luckCode\database\table\Table;
use function date_create;
use function timezone_open;

class LuckTable extends Table
{

    public static $name = 'luckcode_default';

    /**
     * @inheritDoc
     */
    public function getCreationExecute(): string
    {
        $table = $this->name();
        return "CREATE TABLE IF NOT EXISTS `$table` (`name` VARCHAR(30) NOT NULL, `time` TEXT NOT NULL)";
    }

    /**
     * @param string $player
     * @return bool
     */
    public function hasPlayer(string $player) : bool {
        $table = $this->name();
        return !empty($this->provider->executeQuery("SELECT * FROM `$table` WHERE `name` = '$player'"));
    }

    /**
     * @param string $player
     * @return bool
     */
    public function registerNew(string $player) : bool {
        $time = date_create('now', timezone_open('America/Sao_Paulo'))->format("Y/m/d h:i:s");
        $table = $this->name();
        return $this->provider->exec("INSERT INTO `$table` (`name`, `time`) VALUES ('$player', '$time')");
    }
}