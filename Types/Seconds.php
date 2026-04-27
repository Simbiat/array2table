<?php
declare(strict_types = 1);

namespace Simbiat\HTML\ArrayToTable\Types;

use Simbiat\HTML\ArrayToTable\AbstractType;
use Simbiat\SandClock;

/**
 * Abstract class for different data types
 */
class Seconds extends AbstractType
{
    /**
     * @var bool Whether to use SandClock class to style text representation
     */
    public static bool $sand_clock = true;
    /**
     * @var bool Whether to use full words (`true`) or just `:` separator (`false`, output will look like `1:1:5:8:5:6:1:1:7:10:52`)
     */
    public static bool $full = true;
    /**
     * @var string Language to use. Needs to be one of the values supported by SandClock
     */
    public static string $language = 'en';
    /**
     * @var bool Whether to use ISO 8601 duration format, that will produce string like `P51Y8M0W4DT8H20M31S`
     */
    public static bool $iso = false;
    
    /**
     * Generate regular HTML representation
     * @param mixed $value
     *
     * @return string
     * @throws \Exception
     */
    protected function regular(mixed $value): string
    {
        if (self::$sand_clock) {
            $value = SandClock::seconds($value, self::$full, self::$language, self::$iso);
        }
        return $value;
    }
    
    /**
     * Generates editable version of the element
     * @param mixed $value
     *
     * @return string
     */
    protected function editable(mixed $value): string
    {
        return /** @lang HTML */ '<input id="'.$this->id.'" type="number" step="1" min="0" inputmode="decimal" pattern="[0-9]+" value="'.$value.'">';
    }
}