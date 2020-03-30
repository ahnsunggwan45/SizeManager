<?php

namespace ojy\size;

use name\uimanager\CustomForm;
use name\uimanager\element\Button;
use name\uimanager\element\Label;
use name\uimanager\element\Slider;
use name\uimanager\element\StepSlider;
use name\uimanager\event\ModalFormResponseEvent;
use name\uimanager\ModalForm;
use name\uimanager\SimpleForm;
use name\uimanager\UIManager;
use ojy\size\SizeManager;
use pocketmine\block\BlockIds;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\Player;
use pocketmine\Server;
use ssss\utils\SSSSUtils;

class EventListener implements Listener
{

    public function __construct()
    {
        Server::getInstance()->getPluginManager()->registerEvents($this, SizeManager::$i);
    }

    /*public function onTeleport(EntityTeleportEvent $event)
    {
        $player = $event->getEntity();
        if ($player instanceof Player) {
            $preventSizeWorld = ["pvp", "world0", "world1", "world2", "world3", "길드전"];
            $to = $event->getTo();
            if (in_array($to->level->getFolderName(), $preventSizeWorld)) {
                $player->setScale(1);
            } else {
                $player->setScale(SizeManager::getSize($player));
            }
        }
    }*/

    /*public function onMove(PlayerMoveEvent $event)
    {
        if (!$event->getFrom()->equals($event->getTo())) {
            $player = $event->getPlayer();
            $downId = $player->level->getBlock($player->getSide(Vector3::SIDE_DOWN))->getId();
            //Move To Trampoline Event
        }

    }*/

    public const MAIN_ID = 77766776;

    public static function sendMainSizeUI(Player $player)
    {
        $nowSize = SizeManager::getSize($player);
        $allCount = 0;
        foreach ($player->getInventory()->all(Item::get(399, 1)) as $item)
            if ($item instanceof Item)
                $allCount += $item->getCount();
        $form = new ModalForm("§l크기 상점", "현재 크기: {$nowSize}\n보유중인 코인 수: {$allCount}", "§l§6<< §8크기 작아지기 §6>>",
            "§l§6<< §8크기 커지기 §6>>");
        UIManager::getInstance()->sendUI($player, $form, self::MAIN_ID);
    }

    public static function canSmall(Player $player): bool
    {
        $size = SizeManager::getSize($player);
        return $size > 0.5;
    }

    public static function canBigger(Player $player): bool
    {
        $size = SizeManager::getSize($player);
        return $size < 2.0;
    }

    public const SMALL_ID = 615261625;

    /**
     * @param Player $player
     * @throws \Exception
     */
    public static function sendSmallSizeUI(Player $player)
    {
        $minSize = 0.6;
        $nowSize = SizeManager::getSize($player);
        $canSmall = ($nowSize - $minSize) * 10;
        $allCount = 0;
        foreach ($player->getInventory()->all(Item::get(399, 1)) as $item)
            if ($item instanceof Item)
                $allCount += $item->getCount();
        if ($canSmall > $allCount)
            $canSmall = $allCount;
        $form = new CustomForm("§l작아지는 크기상점");
        if ($canSmall < 1)
            $form->addElement(new Label("크기코인이 없습니다."));
        else
            $form->addElement(new Label("현재 크기: {$nowSize}\n작아질 수 있는 정도: {$canSmall}"));
        $form->addElement(new Slider("작아질 정도", 0, $canSmall));
        UIManager::getInstance()->sendUI($player, $form, self::SMALL_ID);
    }

    public const BIGGER_ID = 615261425;

    /**
     * @param Player $player
     * @throws \Exception
     */
    public static function sendBigSizeUI(Player $player)
    {
        $maxSize = 2.0;
        $nowSize = SizeManager::getSize($player);
        $canBigger = ($maxSize - $nowSize) * 10;
        $allCount = 0;
        foreach ($player->getInventory()->all(Item::get(399, 1)) as $item)
            if ($item instanceof Item)
                $allCount += $item->getCount();
        if ($canBigger > $allCount)
            $canBigger = $allCount;
        $form = new CustomForm("§l커지는 크기상점");
        if ($canBigger < 1)
            $form->addElement(new Label("크기코인이 없습니다."));
        else
            $form->addElement(new Label("현재 크기: {$nowSize}\n커질 수 있는 정도: {$canBigger}"));
        $form->addElement(new Slider("커질 정도", 0, $canBigger));
        UIManager::getInstance()->sendUI($player, $form, self::BIGGER_ID);
    }

    /**
     * @param ModalFormResponseEvent $event
     * @throws \Exception
     */
    public function recvPk(ModalFormResponseEvent $event)
    {
        $data = $event->getFormData();
        switch ($event->getFormId()) {
            case self::MAIN_ID:
                if ($data !== null) {
                    if ($data === true) {
                        if (self::canSmall($event->getPlayer()))
                            self::sendSmallSizeUI($event->getPlayer());
                        else
                            SSSSUtils::message($event->getPlayer(), "더 작아질 수 없는 크기입니다.");
                    } elseif ($data === false) {
                        if (self::canBigger($event->getPlayer()))
                            self::sendBigSizeUI($event->getPlayer());
                        else
                            SSSSUtils::message($event->getPlayer(), "더 커질 수 없는 크기입니다.");
                    }
                }
                break;
            case self::SMALL_ID:
                if ($data !== null) {
                    if ($data[1] !== null && $data[1] > 0) {
                        $player = $event->getPlayer();
                        if ($player->getInventory()->contains(Item::get(399, 1, $data[1]))) {
                            $player->getInventory()->removeItem(Item::get(399, 1, $data[1]));
                            $nowSize = SizeManager::getSize($player);
                            SizeManager::setSize($player, $nowSize - $data[1] / 10);
                        }
                    }
                }
                break;
            case self::BIGGER_ID:
                if ($data !== null) {
                    if ($data[1] !== null && $data[1] > 0) {
                        $player = $event->getPlayer();
                        if ($player->getInventory()->contains(Item::get(399, 1, $data[1]))) {
                            $player->getInventory()->removeItem(Item::get(399, 1, $data[1]));
                            $nowSize = SizeManager::getSize($player);
                            SizeManager::setSize($player, $nowSize + $data[1] / 10);
                        }
                    }
                }
                break;

        }

    }
}