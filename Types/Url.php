<?php
declare(strict_types = 1);

namespace Simbiat\HTML\ArrayToTable\Types;

use Simbiat\HTML\ArrayToTable\AbstractType;
use Simbiat\http20\IRI;
use Simbiat\StringHelpers\Convert;

/**
 * Abstract class for different data types
 */
class Url extends AbstractType
{
    /**
     * @var bool Whether to use PrettyURL class to prettify URL
     */
    public static bool $pretty = false;
    /**
     * @var string Symbol to replace whitespace with (for PrettyURL)
     */
    public static string $whitespace = '_blank';
    /**
     * @var string Target attribute value for `<a>` tags
     */
    public static string $target = '_blank';
    
    /**
     * Generate regular HTML representation
     * @param bool|int|float|string $value
     *
     * @return string
     */
    protected function regular(mixed $value): string
    {
        return /** @lang HTML */ '<a id="'.$this->id.'" href="'.$this->check($value).'" target="'.self::$target.'" rel="noreferrer">';
    }
    
    /**
     * Generates editable version of the element
     * @param mixed $value
     *
     * @return string
     */
    protected function editable(mixed $value): string
    {
        return /** @lang HTML */ '<input id="'.$this->id.'" type="url" inputmode="url" value="'.$this->check($value).'">';
    }
    
    /**
     * Check if value is a valid URL, if it's not empty
     * @param mixed $value
     *
     * @return string
     */
    private function check(mixed $value): string
    {
        if (!empty($value)) {
            if (self::$pretty) {
                $value = Convert::prettyURL($value, self::$whitespace);
            }
            if (!IRI::isValidIri($value)) {
                #Not a URL, something is wrong, protect ourselves
                throw new \UnexpectedValueException('`'.$value.'` is not a valid URL.');
            }
        }
        return $value;
    }
}