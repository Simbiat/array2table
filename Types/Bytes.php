<?php
declare(strict_types = 1);

namespace Simbiat\HTML\ArrayToTable\Types;

use Simbiat\CuteBytes;
use Simbiat\HTML\ArrayToTable\AbstractType;

/**
 * Abstract class for different data types
 */
class Bytes extends AbstractType
{
    /**
     * @var bool Whether to use CuteBytes class to style text representation
     */
    public static bool $cute_bytes = true;
    /**
     * @var int Power used in conversion. Whether comply with decimal SI (1000) or binary IEC 80000-13 (1024).
     */
    public static int $power = 1000;
    /**
     * @var int Number of decimals after decimal delimiter.
     */
    public static int $decimals = 2;
    /**
     * @var string Decimal delimiter. if empty, `.` will be forced.
     */
    public static string $dec_point = '.';
    /**
     * @var string Thousands' separator, `,` by default.
     */
    public static string $thousands_sep = ',';
    /**
     * @var int How many 'extra' numbers to show. For example, 3 will attempt to show thousands, where applicable. 0 by default for more "conventional" look.
     */
    public static int $numbers = 0;
    /**
     * @var bool Whether provided value is bits, not bytes.
     */
    public static bool $bits = false;
    
    /**
     * Generate regular HTML representation
     * @param mixed $value
     *
     * @return string
     * @throws \Exception
     */
    protected function regular(mixed $value): string
    {
        if (self::$cute_bytes) {
            $value = CuteBytes::bytes($value, self::$power, self::$decimals, self::$dec_point, self::$thousands_sep, self::$numbers, self::$bits);
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