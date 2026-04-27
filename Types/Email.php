<?php
declare(strict_types = 1);

namespace Simbiat\HTML\ArrayToTable\Types;

use Simbiat\HTML\ArrayToTable\AbstractType;

/**
 * Abstract class for different data types
 */
class Email extends AbstractType
{
    
    /**
     * Generate regular HTML representation
     * @param mixed $value
     *
     * @return string
     */
    protected function regular(mixed $value): string
    {
        return /** @lang HTML */ '<a id="'.$this->id.'" href="mailto:'.$this->check($value).'" rel="noopener noreferrer">';
    }
    
    /**
     * Generates editable version of the element
     * @param mixed $value
     *
     * @return string
     */
    protected function editable(mixed $value): string
    {
        return /** @lang HTML */ '<input id="'.$this->id.'" type="email" inputmode="email" value="'.$this->check($value).'">';
    }
    
    /**
     * Check if value is a valid email, if it's not empty
     * @param mixed $value
     *
     * @return string
     */
    private function check(mixed $value): string
    {
        if (!empty($value) && \filter_var($value, \FILTER_VALIDATE_EMAIL, \FILTER_FLAG_EMAIL_UNICODE) === false) {
            #Not an email, something is wrong, protect ourselves
            throw new \UnexpectedValueException('`'.$value.'` is not a valid email.');
        }
        return $value;
    }
}