<?php

namespace ojy\size\event;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use pocketmine\event\HandlerList;
use pocketmine\Player;

class SizeChangeEvent extends Event implements Cancellable
{

    /** @var HandlerList|null */
    public static $handlerList = null;

    /** @var Player */
    protected $player;

    /** @var float */
    protected $size;

    public function __construct(Player $player, float $size)
    {
        $this->player = $player;
        $this->size;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getSize(): float
    {
        return $this->size;
    }
}