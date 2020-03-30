<?php

namespace ojy\size;

use ojy\size\event\SizeChangeEvent;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class SizeManager extends PluginBase
{

    /** @var Config */
    public static $data;

    /** @var array */
    public static $db = [];

    /** @var self */
    public static $i;

    public function onLoad()
    {
        self::$i = $this;
    }

    public function onEnable()
    {
        self::$data = new Config($this->getDataFolder() . "Data.yml", Config::YAML, ["size" => []]);
        self::$db = self::$data->getAll();
        \o\c\c::command("크기상점", "크기상점 UI를 실행합니다.", "/크기상점", [], function (CommandSender $sender, string $commandLabel, array $args) {
            if ($sender instanceof Player)
                EventListener::sendMainSizeUI($sender);
        });

        new EventListener();
    }

    public static function getSize(Player $player): float
    {
        return self::$db["size"][$player->getName()] ?? 1;
    }

    public static function setSize(Player $player, float $size, bool $force = false)
    {
        self::$db["size"][$player->getName()] = $size;
        $ev = new SizeChangeEvent($player, $size);
        $ev->call();
        if (!$ev->isCancelled() || $force) {
            $player->setScale($size);
        }
    }

    public function onDisable()
    {
        self::$data->setAll(self::$db);
        self::$data->save();
    }
}