<?php
namespace DrdPlus\Tables\Theurgist\Demons\DemonParameters;

use DrdPlus\BaseProperties\Strength;
use DrdPlus\Tables\Theurgist\Spells\SpellParameters\Partials\CastingParameter;

class DemonStrength extends CastingParameter
{
    public function getStrength(): Strength
    {
        return Strength::getIt($this->getValue());
    }
}