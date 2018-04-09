<?php
namespace DrdPlus\Calculators\Destruction;

use DrdPlus\Codes\Environment\MaterialCode;
use DrdPlus\Codes\Units\SquareUnitCode;
use DrdPlus\Codes\Units\TimeUnitCode;
use DrdPlus\Codes\Units\VolumeUnitCode;
use DrdPlus\Destruction\BaseTimeOfDestruction;
use DrdPlus\Destruction\Destruction;
use DrdPlus\Destruction\MaterialResistance;
use DrdPlus\Destruction\PowerOfDestruction;
use DrdPlus\Destruction\RealTimeOfDestruction;
use DrdPlus\Destruction\RollOnDestruction;
use DrdPlus\DiceRolls\Templates\Rollers\Roller2d6DrdPlus;
use DrdPlus\Properties\Base\Strength;
use DrdPlus\Properties\Body\Size;
use DrdPlus\RollsOn\QualityAndSuccess\RollOnQuality;
use DrdPlus\Tables\Measurements\Fatigue\Fatigue;
use DrdPlus\Tables\Measurements\Partials\Exceptions\RequestedDataOutOfTableRange;
use DrdPlus\Tables\Measurements\Square\Square;
use DrdPlus\Tables\Measurements\Time\Exceptions\CanNotConvertThatBonusToTime;
use DrdPlus\Tables\Measurements\Time\Time;
use DrdPlus\Tables\Measurements\Volume\Volume;
use DrdPlus\Tables\Tables;
use Granam\Integer\IntegerInterface;
use Granam\Integer\IntegerObject;
use DrdPlus\Tables\Measurements\Partials\Exceptions\UnknownBonus;

class Controller extends \DrdPlus\Calculators\AttackSkeleton\Controller
{

    public const VOLUME_UNIT = 'volume_unit';
    public const VOLUME_VALUE = 'volume_value';
    public const SQUARE_UNIT = 'square_unit';
    public const SQUARE_VALUE = 'square_value';
    public const MATERIAL = 'material';
    public const ROLL_ON_DESTRUCTING = 'roll_on_destructing';
    public const SHOULD_ROLL_ON_DESTRUCTING = 'new_roll_on_destructing';
    public const SELECTED_MELEE_WEAPONLIKE = 'selected_melee_weaponlike';
    public const INAPPROPRIATE_TOOL = 'inappropriate_tool';
    public const STRENGTH = 'strength';
    public const WEAPON_HOLDING_VALUE = 'weapon_holding_value';
    public const ITEM_SIZE = 'item_size';
    public const BODY_SIZE = 'body_size';

    /** @var Destruction */
    private $destruction;
    /** @var Tables */
    private $tables;

    /**
     * @param Tables $tables
     * @throws \DrdPlus\Calculators\AttackSkeleton\Exceptions\BrokenNewArmamentValues
     */
    public function __construct(Tables $tables)
    {
        parent::__construct('destruction' /* cookies postfix */);
        $this->destruction = new Destruction($tables);
        $this->tables = $tables;
    }

    /**
     * @return array|MaterialCode[]
     */
    public function getMaterialCodes(): array
    {
        return \array_map(
            function (string $materialValue) {
                return MaterialCode::getIt($materialValue);
            },
            MaterialCode::getPossibleValues()
        );
    }

    /**
     * @param MaterialCode $materialCode
     * @return \DrdPlus\Destruction\MaterialResistance
     * @throws \DrdPlus\Tables\Environments\Exceptions\UnknownMaterialToGetResistanceFor
     */
    public function getMaterialResistance(MaterialCode $materialCode): MaterialResistance
    {
        return $this->destruction->getMaterialResistance($materialCode);
    }

    /**
     * @return array|VolumeUnitCode[]
     */
    public function getVolumeUnits(): array
    {
        return \array_map(
            function (string $volumeUnitValue) {
                return VolumeUnitCode::getIt($volumeUnitValue);
            },
            VolumeUnitCode::getPossibleValues()
        );
    }

    /**
     * @return array|SquareUnitCode[]
     */
    public function getSquareUnits(): array
    {
        return \array_map(
            function (string $squareUnitValue) {
                return SquareUnitCode::getIt($squareUnitValue);
            },
            SquareUnitCode::getPossibleValues()
        );
    }

    public function getSelectedVolumeUnit(): VolumeUnitCode
    {
        $volumeUnitValue = $this->getHistory()->getValue(self::VOLUME_UNIT);
        if ($volumeUnitValue) {
            return VolumeUnitCode::getIt($volumeUnitValue);
        }
        $possibleValues = VolumeUnitCode::getPossibleValues();
        $defaultUnitValue = \reset($possibleValues);

        return VolumeUnitCode::getIt($defaultUnitValue);
    }

    /**
     * @return float
     * @throws \DrdPlus\Calculators\Destruction\Exceptions\UnknownVolumeUnit
     */
    public function getSelectedVolumeValue(): float
    {
        $selectedVolumeUnit = $this->getSelectedVolumeUnit();
        switch ($selectedVolumeUnit->getValue()) {
            case VolumeUnitCode::LITER :
                $minimal = \max(10.0/** minimal liters for @see VolumeTable */, $this->getHistory()->getValue(self::VOLUME_VALUE) ?? 0.0);

                return \min(1000/** maximal liters for @see VolumeTable */, $minimal);
            case VolumeUnitCode::CUBIC_METER :
                $minimal = \max(0.01/** minimal cubic meters for @see VolumeTable */, $this->getHistory()->getValue(self::VOLUME_VALUE) ?? 0.0);

                return \min(1000/** maximal cubic meters for @see VolumeTable */, $minimal);
            case VolumeUnitCode::CUBIC_KILOMETER :
                $minimal = \max(0.001/** minimal cubic kilometers for @see VolumeTable */, $this->getHistory()->getValue(self::VOLUME_VALUE) ?? 0.0);

                return \min(0.9/** maximal cubic meters for @see VolumeTable */, $minimal);
            default :
                throw new Exceptions\UnknownVolumeUnit('Unknown volume unit ' . $selectedVolumeUnit);
        }
    }

    public function getSelectedSquareUnit(): SquareUnitCode
    {
        $squareUnitValue = $this->getHistory()->getValue(self::SQUARE_UNIT);
        if ($squareUnitValue) {
            return SquareUnitCode::getIt($squareUnitValue);
        }
        $possibleValues = SquareUnitCode::getPossibleValues();
        $defaultUnitValue = \reset($possibleValues);

        return SquareUnitCode::getIt($defaultUnitValue);
    }

    /**
     * @return float
     * @throws \DrdPlus\Calculators\Destruction\Exceptions\UnknownSquareUnit
     */
    public function getSelectedSquareValue(): float
    {
        $selectedSquareUnit = $this->getSelectedSquareUnit();
        switch ($selectedSquareUnit->getValue()) {
            case SquareUnitCode::SQUARE_DECIMETER :
                $minimal = \max(10.0/** minimal liters for @see SquareTable */, $this->getHistory()->getValue(self::SQUARE_VALUE) ?? 0.0);

                return \min(1000/** maximal liters for @see SquareTable */, $minimal);
            case SquareUnitCode::SQUARE_METER :
                $minimal = \max(0.01/** minimal cubic meters for @see SquareTable */, $this->getHistory()->getValue(self::SQUARE_VALUE) ?? 0.0);

                return \min(1000/** maximal cubic meters for @see SquareTable */, $minimal);
            case SquareUnitCode::SQUARE_KILOMETER :
                $minimal = \max(0.001/** minimal cubic kilometers for @see SquareTable */, $this->getHistory()->getValue(self::SQUARE_VALUE) ?? 0.0);

                return \min(0.9/** maximal cubic meters for @see SquareTable */, $minimal);
            default :
                throw new Exceptions\UnknownSquareUnit('Unknown volume unit ' . $selectedSquareUnit);
        }
    }

    public function getSelectedMaterial(): MaterialCode
    {
        $materialValue = $this->getHistory()->getValue(self::MATERIAL);
        if ($materialValue) {
            return MaterialCode::getIt($materialValue);
        }
        $possibleValues = MaterialCode::getPossibleValues();
        $defaultMaterialValue = \reset($possibleValues);

        return MaterialCode::getIt($defaultMaterialValue);
    }

    /**
     * @return IntegerInterface
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     */
    public function getSelectedRollOnDestructing(): IntegerInterface
    {
        static $newRollOnDestructing = null;
        if ($newRollOnDestructing === null && $this->shouldRollOnDestructing()) {
            $newRollOnDestructing = Roller2d6DrdPlus::getIt()->roll()->getValue();
        }

        return new IntegerObject(
            $newRollOnDestructing ?? $this->getHistory()->getValue(self::ROLL_ON_DESTRUCTING) ?? 6
        );
    }

    /**
     * @return bool
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     */
    public function shouldRollOnDestructing(): bool
    {
        return (bool)$this->getHistory()->getValue(self::SHOULD_ROLL_ON_DESTRUCTING);
    }

    public function getSelectedInappropriateTool(): bool
    {
        // false by default
        return (bool)$this->getHistory()->getValue(self::INAPPROPRIATE_TOOL);
    }

    /**
     * @return Strength
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     */
    public function getSelectedStrength(): Strength
    {
        return Strength::getIt($this->getHistory()->getValue(self::STRENGTH) ?? 0);
    }

    /**
     * @return IntegerInterface
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     */
    public function getSelectedItemSize(): IntegerInterface
    {
        return new IntegerObject($this->getHistory()->getValue(self::ITEM_SIZE) ?? 0);
    }

    public function getSelectedBodySize(): Size
    {
        return Size::getIt($this->getHistory()->getValue(self::BODY_SIZE) ?? 0);
    }

    /**
     * @return Time|null
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotUseMeleeWeaponlikeBecauseOfMissingStrength
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownArmament
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownMeleeWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByTwoHands
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByOneHand
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \DrdPlus\Tables\Environments\Exceptions\UnknownMaterialToGetResistanceFor
     */
    public function getTimeOfVoluminousItemDestruction(): ?Time
    {
        try {
            return $this->getTimeOfDestruction($this->getRealTimeOfVoluminousItemDestruction());
        } catch (UnknownBonus $unknownBonus) {
            return null;
        }
    }

    /**
     * @return RealTimeOfDestruction
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotUseMeleeWeaponlikeBecauseOfMissingStrength
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownArmament
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownMeleeWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByTwoHands
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByOneHand
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \DrdPlus\Tables\Environments\Exceptions\UnknownMaterialToGetResistanceFor
     */
    private function getRealTimeOfVoluminousItemDestruction(): RealTimeOfDestruction
    {
        $volume = new Volume($this->getSelectedVolumeValue(), $this->getSelectedVolumeUnit(), $this->tables->getDistanceTable());

        return new RealTimeOfDestruction(
            BaseTimeOfDestruction::createForItemOfVolume($volume->getBonus(), $this->tables->getTimeTable()),
            $this->getRollOnDestruction(),
            $this->tables
        );
    }

    public function getFatigueFromVoluminousItemDestruction(): ?Fatigue
    {
        try {
            return $this->getRealTimeOfVoluminousItemDestruction()->getFatigue();
        } catch (CanNotConvertThatBonusToTime $canNotConvertThatBonusToTime) {
            return null;
        } catch (RequestedDataOutOfTableRange $canNotConvertThatBonusToTime) {
            return null;
        }
    }

    public function getFatigueFromSquareItemDestruction(): ?Fatigue
    {
        try {
            return $this->getRealTimeOfSquareItemDestruction()->getFatigue();
        } catch (CanNotConvertThatBonusToTime $canNotConvertThatBonusToTime) {
            return null;
        } catch (RequestedDataOutOfTableRange $canNotConvertThatBonusToTime) {
            return null;
        }
    }

    /**
     * @return RealTimeOfDestruction
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotUseMeleeWeaponlikeBecauseOfMissingStrength
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownArmament
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownMeleeWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByTwoHands
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByOneHand
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \DrdPlus\Tables\Environments\Exceptions\UnknownMaterialToGetResistanceFor
     */
    private function getRealTimeOfSquareItemDestruction(): RealTimeOfDestruction
    {
        $square = new Square($this->getSelectedSquareValue(), $this->getSelectedSquareUnit(), $this->tables->getDistanceTable());

        return new RealTimeOfDestruction(
            BaseTimeOfDestruction::createForItemOfSquare($square->getBonus(), $this->tables->getTimeTable()),
            $this->getRollOnDestruction(),
            $this->tables
        );
    }

    /**
     * @return Time|null
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotUseMeleeWeaponlikeBecauseOfMissingStrength
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownArmament
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownMeleeWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByTwoHands
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByOneHand
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \DrdPlus\Tables\Environments\Exceptions\UnknownMaterialToGetResistanceFor
     */
    public function getTimeOfSquareItemDestruction(): ?Time
    {
        try {
            return $this->getTimeOfDestruction($this->getRealTimeOfSquareItemDestruction());
        } catch (CanNotConvertThatBonusToTime $canNotConvertThatBonusToTime) {
            return null;
        } catch (RequestedDataOutOfTableRange $canNotConvertThatBonusToTime) {
            return null;
        }
    }

    /**
     * @return Time|null
     * @throws \DrdPlus\Calculators\Destruction\Exceptions\UnknownVolumeUnit
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotUseMeleeWeaponlikeBecauseOfMissingStrength
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownArmament
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownMeleeWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByTwoHands
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByOneHand
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \DrdPlus\Tables\Environments\Exceptions\UnknownMaterialToGetResistanceFor
     */
    public function getTimeOfBasicItemDestruction(): ?Time
    {
        return $this->getTimeOfDestruction($this->getRealTimeOfBasicItemDestruction());
    }

    private function getTimeOfDestruction(RealTimeOfDestruction $realTimeOfDestruction): ?Time
    {
        $time = $realTimeOfDestruction->findTime(TimeUnitCode::HOUR);
        if (!$time) {
            $time = $realTimeOfDestruction->findTime();
        }
        if (!$time) {
            return null;
        }
        if ($time->getValue() > 1) {
            return $time;
        }
        $timeWithLesserUnit = $time->findInLesserUnit();
        while ($timeWithLesserUnit !== null && $timeWithLesserUnit->getValue() < 1) {
            $timeWithLesserUnit = $timeWithLesserUnit->findInLesserUnit();
        }

        return $timeWithLesserUnit ?? $time;
    }

    /**
     * @return RealTimeOfDestruction
     * @throws \DrdPlus\Calculators\Destruction\Exceptions\UnknownVolumeUnit
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotUseMeleeWeaponlikeBecauseOfMissingStrength
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownArmament
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownMeleeWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByTwoHands
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByOneHand
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \DrdPlus\Tables\Environments\Exceptions\UnknownMaterialToGetResistanceFor
     */
    private function getRealTimeOfBasicItemDestruction(): RealTimeOfDestruction
    {
        return new RealTimeOfDestruction(
            BaseTimeOfDestruction::createForItemSize($this->getSelectedItemSize(), $this->tables->getTimeTable()),
            $this->getRollOnDestruction(),
            $this->tables
        );
    }

    public function getBasicItemDestructionFatigue(): ?Fatigue
    {
        try {
            return $this->getRealTimeOfBasicItemDestruction()->getFatigue();
        } catch (CanNotConvertThatBonusToTime $canNotConvertThatBonusToTime) {
            return null;
        } catch (RequestedDataOutOfTableRange $canNotConvertThatBonusToTime) {
            return null;
        }
    }

    /**
     * @return RollOnDestruction
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotUseMeleeWeaponlikeBecauseOfMissingStrength
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownArmament
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownMeleeWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByTwoHands
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByOneHand
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \DrdPlus\Tables\Environments\Exceptions\UnknownMaterialToGetResistanceFor
     */
    public function getRollOnDestruction(): RollOnDestruction
    {
        return $this->destruction->getRollOnDestruction(
            $this->getPowerOfDestruction(),
            $this->getMaterialResistance($this->getSelectedMaterial()),
            $this->getRollOnDestructing()
        );
    }

    /**
     * @return PowerOfDestruction
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotUseMeleeWeaponlikeBecauseOfMissingStrength
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownArmament
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownMeleeWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByTwoHands
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByOneHand
     */
    public function getPowerOfDestruction(): PowerOfDestruction
    {
        return $this->destruction->getPowerOfDestruction(
            $this->getAttack()->getCurrentMeleeWeapon(),
            $this->getCurrentProperties()->getCurrentStrength(),
            $this->getAttack()->getCurrentMeleeWeaponHolding(),
            $this->getSelectedInappropriateTool()
        );
    }

    /**
     * @return RollOnQuality
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     */
    public function getRollOnDestructing(): RollOnQuality
    {
        return new RollOnQuality(
            0 /* no preconditions */,
            Roller2d6DrdPlus::getIt()->generateRoll($this->getSelectedRollOnDestructing())
        );
    }

    public function getTimeOfStatueLikeDestruction(): ?Time
    {
        return $this->getTimeOfDestruction($this->getRealTimeOfStatueLikeDestruction());
    }

    /**
     * @return RealTimeOfDestruction
     * @throws \DrdPlus\Calculators\Destruction\Exceptions\UnknownVolumeUnit
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotUseMeleeWeaponlikeBecauseOfMissingStrength
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownArmament
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\UnknownMeleeWeaponlike
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByTwoHands
     * @throws \DrdPlus\Tables\Armaments\Exceptions\CanNotHoldWeaponByOneHand
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \DrdPlus\Tables\Environments\Exceptions\UnknownMaterialToGetResistanceFor
     */
    private function getRealTimeOfStatueLikeDestruction(): RealTimeOfDestruction
    {
        return new RealTimeOfDestruction(
            BaseTimeOfDestruction::createForBodySize($this->getSelectedBodySize(), $this->tables->getTimeTable()),
            $this->getRollOnDestruction(),
            $this->tables
        );
    }

    public function getFatigueFromStatueLikeDestruction(): ?Fatigue
    {
        try {
            return $this->getRealTimeOfStatueLikeDestruction()->getFatigue();
        } catch (CanNotConvertThatBonusToTime $canNotConvertThatBonusToTime) {
            return null;
        } catch (RequestedDataOutOfTableRange $canNotConvertThatBonusToTime) {
            return null;
        }
    }
}