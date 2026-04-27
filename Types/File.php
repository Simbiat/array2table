<?php
declare(strict_types = 1);

namespace Simbiat\HTML\ArrayToTable\Types;

use Simbiat\HTML\ArrayToTable\AbstractType;

/**
 * Abstract class for different data types
 */
class File extends AbstractType
{
    
    /**
     * Generate regular HTML representation
     * @param bool|int|float|string $value
     *
     * @return string
     */
    abstract protected function regular(bool|int|float|string $value): string
    {
    
    }
    
    /**
     * Generates editable version of the element
     * @param bool|int|float|string $value
     *
     * @return string
     */
    abstract protected function editable(bool|int|float|string $value): string
    {
    
    }
}