<?php
declare(strict_types = 1);

namespace Simbiat\HTML\ArrayToTable;

use function get_class;

/**
 * Abstract class for different data types
 */
abstract class AbstractType
{
    /**
     * @var bool Whether to try to sanitize the values with some standard functions. Does not make the values trusted even when enabled (default).
     */
    private(set) static bool $sanitize = true;
    protected static int $counter = 1;
    protected string $id = '';
    
    /**
     * Generate ID for the element
     * @param string   $prefix Prefix to use for the ID
     * @param int|null $footer
     *
     * @return string
     */
    protected function getId(string $prefix = 'simbiat_', null|int $footer = null): string
    {
        if ($footer === null) {
            return $prefix.mb_strtolower(get_class($this), 'UTF-8').'_'.self::$counter;
        }
        return $prefix.'footer_'.$footer;
    }
    
    /**
     * Main function to generate HTML representation of the element
     * @param bool|int|float|string $value
     * @param string                $prefix
     * @param bool                  $editable
     * @param int|null              $footer
     *
     * @return string
     */
    public function generate(mixed $value, string $prefix = 'simbiat_', bool $editable = false, null|int $footer = null): string
    {
        #Set the ID of the element
        $this->id = $this->getId($prefix, $footer);
        $value = (string)$value;
        #Sanitize the value. Note that this does not automatically make the values trusted
        if (self::$sanitize) {
            $value = \stripslashes($value);
            $value = \strip_tags($value);
            $value = \htmlentities($value, \ENT_QUOTES | \ENT_SUBSTITUTE);
        }
        return $editable && $footer === null ? $this->editable($value) : $this->regular($value);
    }
    
    /**
     * Generate regular HTML representation
     * @param mixed $value
     *
     * @return string
     */
    abstract protected function regular(mixed $value): string;
    
    /**
     * Generates the editable version of the element
     * @param mixed $value
     *
     * @return string
     */
    abstract protected function editable(mixed $value): string;
}