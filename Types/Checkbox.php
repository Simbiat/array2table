<?php
declare(strict_types = 1);

namespace Simbiat\HTML\ArrayToTable\Types;

use Simbiat\HTML\ArrayToTable\AbstractType;
use function is_string;

/**
 * Abstract class for different data types
 */
class Checkbox extends AbstractType
{
    
    /**
     * Generate regular HTML representation
     * @param mixed $value
     *
     * @return string
     */
    protected function regular(mixed $value): string
    {
        return /** @lang HTML */ '<input id="'.$this->id.'" type="checkbox"'.$this->toCheckbox($value).' disabled>';
    }
    
    /**
     * Generates editable version of the element
     * @param mixed $value
     *
     * @return string
     */
    protected function editable(mixed $value): string
    {
        return /** @lang HTML */ '<input id="'.$this->id.'" type="checkbox"'.$this->toCheckbox($value).'>';
    }
    
    /**
     * Convert a value to checkbox status
     * @param mixed $value
     *
     * @return string
     */
    private function toCheckbox(mixed $value): string
    {
        if (is_string($value) && \preg_match('/^on|yes$/mi', $value) === 1) {
            return ' checked';
        }
        if (is_string($value) && \preg_match('/^off|no$/mi', $value) === 1) {
            return '';
        }
        if ((bool)$value === true) {
            return ' checked';
        }
        return '';
    }
}