<?php
declare(strict_types=1);
namespace jasonwynn10\EasyCommandAutofill;

use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\CommandData;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandEnumConstraint;
use pocketmine\network\mcpe\protocol\UpdateSoftEnumPacket;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase{
	/** @var CommandData[] $manualOverrides */
	protected $manualOverrides = [];
	/** @var string[] $debugCommands */
	protected $debugCommands = [];
	/** @var CommandEnum[] $hardcodedEnums */
	protected $hardcodedEnums = [];
	/** @var CommandEnum[] $softEnums */
	protected $softEnums = [];
	/** @var CommandEnumConstraint[] $enumConstraints */
	protected $enumConstraints = [];

	public function onEnable() {
		new EventListener($this);
		if($this->getConfig()->get("Highlight-Debug", true))
			$this->debugCommands = ["dumpmemory", "gc", "timings", "status"];
	}

	/**
	 * @param string $commandName
	 * @param CommandData $data
	 *
	 * @return self
	 */
	public function addManualOverride(string $commandName, CommandData $data) : self {
		$this->manualOverrides[$commandName] = $data;
		foreach($this->getServer()->getOnlinePlayers() as $player) {
			$player->sendDataPacket(new AvailableCommandsPacket());
		}
		return $this;
	}

	/**
	 * @return CommandData[]
	 */
	public function getManualOverrides() : array {
		return $this->manualOverrides;
	}

	/**
	 * @param string $commandName
	 *
	 * @return self
	 */
	public function addDebugCommand(string $commandName) : self {
		$this->debugCommands[] = $commandName;
		foreach($this->getServer()->getOnlinePlayers() as $player) {
			$player->sendDataPacket(new AvailableCommandsPacket());
		}
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getDebugCommands() : array {
		return $this->debugCommands;
	}

	/**
	 * @param CommandEnum $enum
	 *
	 * @return self
	 */
	public function addHardcodedEnum(CommandEnum $enum) : self {
		foreach($this->softEnums as $softEnum)
			if($enum->enumName === $softEnum->enumName)
				throw new \InvalidArgumentException("Hardcoded enum is already in soft enum list.");
		$this->hardcodedEnums[] = $enum;
		foreach($this->getServer()->getOnlinePlayers() as $player) {
			$player->sendDataPacket(new AvailableCommandsPacket());
		}
		return $this;
	}

	/**
	 * @return CommandEnum[]
	 */
	public function getHardcodedEnums() : array {
		return $this->hardcodedEnums;
	}

	/**
	 * @param CommandEnum $enum
	 *
	 * @return self
	 */
	public function addSoftEnum(CommandEnum $enum) : self {
		foreach($this->hardcodedEnums as $hardcodedEnum)
			if($enum->enumName === $hardcodedEnum->enumName)
				throw new \InvalidArgumentException("Soft enum is already in hardcoded enum list.");
		$this->softEnums[] = $enum;
		$pk = new UpdateSoftEnumPacket();
		$pk->enumName = $enum->enumName;
		$pk->values = $enum->enumValues;
		$pk->type = UpdateSoftEnumPacket::TYPE_ADD;
		$this->getServer()->broadcastPacket($this->getServer()->getOnlinePlayers(), $pk);
		return $this;
	}

	/**
	 * @return CommandEnum[]
	 */
	public function getSoftEnums() : array {
		return $this->softEnums;
	}

	/**
	 * @param CommandEnum $enum
	 *
	 * @return self
	 */
	public function updateSoftEnum(CommandEnum $enum) : self {
		$pk = new UpdateSoftEnumPacket();
		$pk->enumName = $enum->enumName;
		$pk->values = $enum->enumValues;
		$pk->type = UpdateSoftEnumPacket::TYPE_SET;
		$this->getServer()->broadcastPacket($this->getServer()->getOnlinePlayers(), $pk);
		return $this;
	}

	/**
	 * @param CommandEnum $enum
	 *
	 * @return self
	 */
	public function removeSoftEnum(CommandEnum $enum) : self {
		$pk = new UpdateSoftEnumPacket();
		$pk->enumName = $enum->enumName;
		$pk->values = $enum->enumValues;
		$pk->type = UpdateSoftEnumPacket::TYPE_REMOVE;
		$this->getServer()->broadcastPacket($this->getServer()->getOnlinePlayers(), $pk);
		return $this;
	}

	/**
	 * @param CommandEnumConstraint $enumConstraint
	 *
	 * @return Main
	 */
	public function addEnumConstraint(CommandEnumConstraint $enumConstraint) : self {
		foreach($this->hardcodedEnums as $hardcodedEnum)
			if($enumConstraint->getEnum()->enumName === $hardcodedEnum->enumName) {
				$this->enumConstraints[] = $enumConstraint;
				foreach($this->getServer()->getOnlinePlayers() as $player) {
					$player->sendDataPacket(new AvailableCommandsPacket());
				}
				return $this;
			}
		throw new \InvalidArgumentException("Soft enum is already in hardcoded enum list.");
	}

	/**
	 * @return CommandEnumConstraint[]
	 */
	public function getEnumConstraints() : array {
		return $this->enumConstraints;
	}
}