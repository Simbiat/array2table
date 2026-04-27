<?php
declare(strict_types = 1);

namespace Simbiat\HTML\ArrayToTable\Types;

use Simbiat\HTML\ArrayToTable\AbstractType;
use Simbiat\SandClock;

/**
 * Abstract class for different data types
 */
class Time extends AbstractType
{
    /**
     * @var bool Whether to use SandClock class to style text representation
     */
    public static bool $sand_clock = false;
    /**
     * @var string Date format to apply to *text* representation of the value.
     */
    public static string $format = 'H:i:s';
    
    /**
     * Generate regular HTML representation
     *
     * @param mixed $value
     *
     * @return string
     * @throws \Exception
     */
    protected function regular(mixed $value): string
    {
        if (self::$sand_clock) {
            $value = SandClock::format($value, self::$format);
        }
        return /** @lang HTML */ '<time id="'.$this->id.'" datetime="'.new \DateTime($value)->format('H:i:s').'">'.$value.'</time>';
    }
    
    /**
     * Generates editable version of the element
     * @param mixed $value
     *
     * @return string
     */
    protected function editable(mixed $value): string
    {
        return /** @lang HTML */ '<input id="'.$this->id.'" type="time" step="1" pattern="[0-9]{2}:[0-9]{2}:[0-9]{2}" value="'.$value.'">';
    }
}