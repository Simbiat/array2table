<?php
# Suppressing inspection for elements' counters, since they are accessed as variable variables
declare(strict_types = 1);

namespace Simbiat\HTML\ArrayToTable;

use JetBrains\PhpStorm\Pure;
use Simbiat\ArrayHelpers\Checkers;
use Simbiat\StringHelpers\Convert;
use function count;
use function in_array;
use function is_array;
use function is_object;

/**
 * Generate HTML table from an array
 */
class Generator
{
    #Generate <table> if true or <div> if false
    private bool $semantic = true;
    #Force basic inline styling for <div> variant. Custom styling is recommended
    private bool $styling = false;
    #Generate with first column being a checkbox with unique ID if true. Works for multi-arrays only
    private bool $checkbox = false;
    #Generate with first column checkbox checked
    private bool $checked = false;
    #Whether to attempt to strip tags and encode HTML entities, if not enforcing HTML
    private bool $sanitize = true;
    #Flag for multidimensional arrays. Using for performance optimization
    private bool $multi_flag = false;
    #Flag to allow edit of the editable fields. Disabled by default
    private bool $editable = false;
    #Optional caption for tables
    private string $caption = '';
    #Optional header to be used
    private array $header = [];
    #Option to repeat header every x lines
    private int $repeat_header = 0;
    #Optional footer to be used
    private array $footer = [];
    #Use header text in footer. Disabled by default
    private bool $footer_header = false;
    #Optional column groups
    private array $colgroup = [];
    #Temporary count of groups for validation purpose
    private int $groups_count = 0;
    #Optional types that allow additional formatting of text
    private array $types = [];
    #Optional prefix for elements' IDs and some classes
    private string $id_prefix = 'simbiat';
    #Option to allow multiple files to be upload in file fields. Disabled by default
    private bool $multiple_files = false;
    
    #Partner libraries flags
    private static bool $pretty_url = false;
    
    #Regex patterns for validations
    public static string $color_hex_regex = '/[^a-fA-F0-9]/m';
    #While this static value can be changed, it is not recommended, unless you are ready to decrease your system security
    public static string $password_pattern = '(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$';
    
    #Currency settings
    private array $currency_settings = ['code' => '', 'precision' => 2, 'fraction' => true];
    #Set default values for 'textarea' type
    private array $textarea_settings = ['rows' => 20, 'cols' => 2, 'minlength' => 0, 'maxlength' => 0];
    
    /**
     * Generate the table
     * @throws \Exception
     */
    public function generate(array $array): string
    {
        if (!self::$pretty_url && \method_exists(Convert::class, 'prettyURL')) {
            self::$pretty_url = true;
        }
        if (empty($array)) {
            throw new \UnexpectedValueException('Empty array provided.');
        }
        #Get length
        if (Checkers::isMultiDimensional($array, true, true)) {
            $this->multi_flag = true;
            $array = \array_values($array);
            $length = \array_unique(\array_map('\count', $array))[0];
        } else {
            $length = count($array);
        }
        #Check if header has the same length
        if (!empty($this->getHeader()) && count($this->getHeader()) !== $length) {
            throw new \UnexpectedValueException('Header was sent, but has different length than array.');
        }
        #Check if footer has the same length
        if (!empty($this->getFooter()) && count($this->getFooter()) !== $length) {
            throw new \UnexpectedValueException('Footer was sent, but has different length than array.');
        }
        #Check if types have the same length
        if (empty($this->getTypes())) {
            #Filling the types' array with 'string' value. Using 'string' so that all elements would be converted to regular <span> elements as a safety precaution
            $this->setTypes(\array_fill(0, $length, 'string'));
        } elseif (count($this->getTypes()) !== $length) {
            throw new \UnexpectedValueException('Types were sent, but have different length than array.');
        }
        #Check if colgroup list has same length
        if ($this->groups_count !== 0 && $this->groups_count !== $length) {
            throw new \UnexpectedValueException('Column groups were sent, but have different length than array.');
        }
        #Disable repeat header if it's equal to length
        if ($this->getRepeatHeader() === $length) {
            $this->setRepeatHeader();
        }
        #Set header
        if ($this->multi_flag) {
            $array = \array_values($array);
            if (!empty($array[0]) && Checkers::isAssociative($array[0]) && empty($this->getHeader())) {
                $this->setHeader(\array_keys($array[0]));
            }
            #Convert data's associative array to regular one for consistency
            $array = \array_values($array);
            $array = \array_map('\array_values', $array);
        } else {
            if (Checkers::isAssociative($array) && empty($this->getHeader())) {
                $this->setHeader(\array_keys($array));
            }
            #Convert data's associative array to regular one for consistency
            $array = \array_values($array);
        }
        #Check if types for each column has the same length as actual column data
        $temp_types = \array_values($this->getTypes());
        foreach ($temp_types as $column => $type_list) {
            if (is_array($type_list)) {
                #Since we are parsing the array either way, convert an associative array to regular one for consistency
                $temp_types[$column] = \array_values($type_list);
                if ($this->multi_flag) {
                    if (count($type_list) !== count($array)) {
                        throw new \UnexpectedValueException('Multi-row types\' column '.$column.' have different length than array.');
                    }
                } else {
                    throw new \UnexpectedValueException('Multi-row types were sent, but data is made up of one row.');
                }
            }
        }
        #Convert types' associative array to regular one for consistency
        $this->setTypes(\array_values($temp_types));
        #Convert the footer associative array to regular one for consistency
        $this->setFooter(\array_values($this->getFooter()));
        #Checking if footer is a function and updating value accordingly
        if (!empty($this->getFooter())) {
            $temp_footer = \array_values($this->getFooter());
            foreach ($temp_footer as $column => $footer) {
                if (in_array($footer, ['#func_sum', '#func_avg', '#func_min', '#func_max'])) {
                    if ($this->multi_flag) {
                        if (is_array($this->getTypes()[$column])) {
                            throw new \UnexpectedValueException('Footer function was sent for column '.$column.', but column has multiple types assigned to it.');
                        }
                        $column_data = \array_column($array, $column);
                        $temp_footer[$column] = match ($footer) {
                            '#func_sum' => 'Sum: '.$this->prepare(\array_sum($column_data), $column, 0, true),
                            '#func_avg' => 'Avg: '.$this->prepare(\array_sum($column_data) / $length, $column, 0, true),
                            '#func_min' => 'Min: '.$this->prepare(\min($column_data), $column, 0, true),
                            '#func_max' => 'Max: '.$this->prepare(\max($column_data), $column, 0, true),
                            default => '',
                        };
                    } else {
                        throw new \UnexpectedValueException('Footer function was sent for column '.$column.', but data is made up of one row.');
                    }
                }
            }
            $this->setFooter($temp_footer);
        }
        return $this->table($array);
    }
    
    /**
     * Resetting values. Useful, in case, someone decides to use same object several times
     * @throws \Exception
     */
    public function default(): self
    {
        $this->setHeader()->setFooter()->setColGroup()->setTypes()->setSemantic()->setEditable()->setCheckbox()->setChecked()->setSanitize()->setCaption()->setRepeatHeader()->setIdPrefix()->setCounter('checkbox', 1)->setCounter('email', 1)->setCounter('url', 1)->setCounter('textarea', 1)->setCounter('file', 1)->setCounter('text', 1)->setCounter('tel', 1)->setCounter('password', 1)->setCounter('img', 1)->setCounter('price', 1)->setCounter('bytes', 1)->setCounter('seconds', 1)->setCounter('datetime', 1)->setCounter('time', 1)->setCounter('date', 1)->setCounter('html', 1)->setMultipleFiles()->setCurrencySettings()->setTextareaSetting()->setFooterHeader()->setStyling();
        return $this;
    }
    
    /**
     * @throws \Exception
     */
    private function table(array $array): string
    {
        #Get prefix
        $prefix_id = $this->getIdPrefix();
        $table = '<'.($this->getSemantic() ? 'table' : 'div'.($this->getStyling() ? ' style="display:table;border-spacing:2px;"' : '')).' id="'.$prefix_id.'table">';
        #Set caption
        if (!empty($this->getCaption())) {
            $table .= '<'.($this->getSemantic() ? 'caption' : 'div'.($this->getStyling() ? ' style="display:table-caption;text-align:center;"' : '')).' id="'.$prefix_id.'caption">'.$this->getCaption().'</'.($this->getSemantic() ? 'caption' : 'div').'>';
        }
        #Set colgroup value
        if ($this->groups_count !== 0 && !empty($this->getColGroup())) {
            $table .= '<'.($this->getSemantic() ? 'colgroup' : 'div'.($this->getStyling() ? ' style="display:table-column-group;"' : '')).' id="'.$prefix_id.'colgroup">';
            foreach ($this->getColGroup() as $key => $group) {
                $table .= '<'.($this->getSemantic() ? 'col' : 'div'.($this->getStyling() ? ' style="display:table-column;"' : '')).'  id="'.$prefix_id.'col_'.$key.'" span="'.$group['span'].'"';
                if (!empty($group['class'])) {
                    $table .= ' class="'.$group['class'].'"';
                }
                if (!empty($group['style'])) {
                    $table .= ' style="'.$group['style'].'"';
                }
                $table .= '>'.($this->getSemantic() ? '' : '</div>');
            }
            $table .= '</'.($this->getSemantic() ? 'colgroup' : 'div').'>';
        }
        #Set header
        if (!empty($this->getHeader())) {
            $table .= '<'.($this->getSemantic() ? 'thead' : 'div'.($this->getStyling() ? ' style="display:table-header-group;font-weight:bold;text-align:center;"' : '')).' class="'.$prefix_id.'thead"><'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).'>';
            #Add checkbox column (empty for header)
            $table .= $this->checkboxColumn($prefix_id);
            $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'></'.($this->getSemantic() ? 'thead' : 'div').'>';
        }
        $table .= '<'.($this->getSemantic() ? 'tbody' : 'div'.($this->getStyling() ? ' style="display:table-row-group;"' : '')).' id="'.$prefix_id.'tbody">';
        #Check if array of array
        if ($this->multi_flag) {
            foreach ($array as $row => $subarray) {
                if ($row > 0 && $this->getRepeatHeader() !== 0 && !empty($this->getHeader()) && \fmod($row + 1, $this->getRepeatHeader()) === 0.0) {
                    $table .= '<'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).'>';
                    #Add checkbox column (empty for header)
                    $table .= $this->checkboxColumn($prefix_id);
                    $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'>';
                }
                $table .= '<'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).' id="'.$prefix_id.'tr_'.$row.'">';
                #Add checkbox column
                if ($this->getCheckbox()) {
                    $table .= '<'.($this->getSemantic() ? 'td' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' class="'.$prefix_id.'checkbox_field" id="'.$prefix_id.'td_checkbox_'.$row.'"><input type="checkbox" id="'.$prefix_id.'checkbox_row_'.$row.'"'.($this->getChecked() ? ' checked' : '').'><label for="'.$prefix_id.'checkbox_row_'.$row.'" class="'.$prefix_id.'checkbox_label">'.($this->getHeader()[$row] ?? '').'</label></'.($this->getSemantic() ? 'td' : 'div').'>';
                }
                $subarray = \array_values($subarray);
                foreach ($subarray as $column => $cell) {
                    $table .= '<'.($this->getSemantic() ? 'td' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' id="'.$prefix_id.'td_'.$row.'_'.$column.'">'.$this->prepare($cell, $column, $row).'</'.($this->getSemantic() ? 'td' : 'div').'>';
                }
                $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'>';
            }
        } else {
            $table .= '<'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).' id="'.$prefix_id.'tr_0">';
            $array = \array_values($array);
            foreach ($array as $column => $cell) {
                $table .= '<'.($this->getSemantic() ? 'td' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' id="'.$prefix_id.'td_0_'.$column.'">'.$this->prepare($cell, $column).'</'.($this->getSemantic() ? 'td' : 'div').'>';
            }
            $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'>';
        }
        $table .= '</'.($this->getSemantic() ? 'tbody' : 'div').'>';
        #Set footer
        if (!empty($this->getFooter())) {
            $table .= '<'.($this->getSemantic() ? 'tfoot' : 'div'.($this->getStyling() ? ' style="display:table-footer-group;font-weight:bold;text-align:center;"' : '')).' id="'.$prefix_id.'tfoot"><'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).'>';
            #Add checkbox column (empty for footer)
            if ($this->getCheckbox()) {
                $table .= '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' class="'.$prefix_id.'checkbox_dummy_field"></'.($this->getSemantic() ? 'th' : 'div').'>';
            }
            foreach ($this->getFooter() as $key => $footer) {
                $table .= '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' class="'.$prefix_id.'th_'.$key.'">'.$footer.'</'.($this->getSemantic() ? 'th' : 'div').'>';
            }
            $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'></'.($this->getSemantic() ? 'tfoot' : 'div').'>';
        } elseif ($this->getFooterHeader() && !empty($this->getHeader())) {
            $table .= '<'.($this->getSemantic() ? 'tfoot' : 'div'.($this->getStyling() ? ' style="display:table-footer-group;font-weight:bold;text-align:center;"' : '')).' id="'.$prefix_id.'tfoot"><'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).'>';
            #Add checkbox column (empty for footer)
            $table .= $this->checkboxColumn($prefix_id);
            $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'></'.($this->getSemantic() ? 'tfoot' : 'div').'>';
        }
        $table .= '</'.($this->getSemantic() ? 'table' : 'div').'>';
        return $table;
    }
    
    /**
     * Function to handle checkbox columns
     *
     * @param string $prefix_id
     *
     * @return string
     */
    #[Pure] private function checkboxColumn(string $prefix_id): string
    {
        $column = '';
        if ($this->getCheckbox()) {
            $column .= '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' class="'.$prefix_id.'checkbox_dummy_field"></'.($this->getSemantic() ? 'th' : 'div').'>';
        }
        foreach ($this->getHeader() as $key => $header) {
            $column .= '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' class="'.$prefix_id.'th_'.$key.'">'.$header.'</'.($this->getSemantic() ? 'th' : 'div').'>';
        }
        return $column;
    }
    
    /**
     * Prepares values according to types
     * @throws \Exception
     */
    private function prepare(bool|int|float|string $string, int $colnum, int $rownum = 0, bool $footer = false): string
    {
        $string = (string)$string;
        #Determine type
        $string_type = $this->getTypes()[$colnum];
        if (is_array($string_type)) {
            $string_type = $string_type[$rownum];
        }
        #Get prefix
        $prefix_id = $this->getIdPrefix();
        #Set ID for the element
        if (in_array($string_type, ['html', 'date', 'time', 'datetime', 'seconds', 'bytes', 'price', 'checkbox', 'email', 'url', 'textarea', 'text', 'tel', 'password', 'img', 'file', 'color'])) {
            if ($footer) {
                ${$string_type.'id'} = $prefix_id.'footer_'.$colnum;
            } else {
                ${$string_type.'id'} = $prefix_id.$string_type.'_'.$this->getCounter($string_type);
            }
        }
        if ($string_type !== 'html' && $this->getSanitize()) {
            $string = \strip_tags($string);
        }
        switch ($string_type) {
            case 'price':
                #Expects integer string, where last 2 numbers are the "fractions" (like cents)
                if ($this->getCurrencySettings()['fraction']) {
                    $string = \substr_replace((string)(int)$string, '.', -$this->getCurrencySettings()['precision'], 0);
                    if (str_starts_with($string, '.')) {
                        $string = '0'.$string;
                    }
                } else {
                    $string = \number_format((float)$string, $this->getCurrencySettings()['precision'], '.', '');
                }
                if ((!$this->getEditable() || $footer) && !empty($this->getCurrencySettings()['code'])) {
                    #If code ends with space - place it before the value
                    if (str_ends_with($this->getCurrencySettings()['code'], ' ')) {
                        $string = $this->getCurrencySettings()['code'].$string;
                    } else {
                        $string .= ' '.$this->getCurrencySettings()['code'];
                    }
                }
                if (!$footer && $this->getEditable()) {
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefix_id.$string_type.'" type="number" step="0.01" min="0.00" inputmode="decimal" value="'.$string.'"><label for="'.${$string_type.'id'}.'" class="'.$prefix_id.$string_type.'_label">'.($this->getHeader()[$colnum] ?? '').'</label>';
                }
                break;
            case 'html':
                #If editable, treat as textarea
                if ($footer || !$this->getEditable()) {
                    $string = \htmlentities($string, \ENT_QUOTES | \ENT_HTML5 | \ENT_DISALLOWED);
                    break;
                }
                break;
            case 'textarea':
                if (!$footer && $this->getEditable()) {
                    $string = '<textarea id="'.${$string_type.'id'}.'" class="'.$prefix_id.$string_type.'" cols="'.$this->textarea_settings['cols'].'" rows="'.$this->textarea_settings['rows'].'" minlength="'.$this->textarea_settings['minlength'].'" maxlength="'.$this->textarea_settings['maxlength'].'" spellcheck="true">'.$string.'</textarea><label for="'.${$string_type.'id'}.'" class="'.$prefix_id.$string_type.'_label">'.($this->getHeader()[$colnum] ?? '').'</label>';
                }
                break;
            case 'text':
            case 'tel':
            case 'password':
                #Removing the entry, in case actual password is sent
                if ($string_type === 'password') {
                    $string = '';
                }
                if (!$footer && $this->getEditable()) {
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefix_id.$string_type.'" type="'.$string_type.'" inputmode="'.$string_type.'" value="'.$string.'"'.($string_type === 'password' ? 'pattern="'.self::$password_pattern.'"' : '').'><label for="'.${$string_type.'id'}.'" class="'.$prefix_id.$string_type.'_label">'.($this->getHeader()[$colnum] ?? '').'</label>';
                }
                break;
            case 'img':
                #If editable, treat as input="file"
                if ($footer || !$this->getEditable()) {
                    #alt is set as "" to make some browsers consider images as non-essential. If an image needs to be considered as essential it's recommended not to show it through this library. Alternatively you can update it through JavaScript or other programmatic methods.
                    $string = '<img loading="lazy" src="'.$string.'" alt="" decoding="async" class="'.$prefix_id.$string_type.'">';
                    break;
                }
                break;
            case 'file':
                $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefix_id.$string_type.'" type="file"'.($this->getEditable() && !$footer ? '' : ' disabled').($this->getMultipleFiles() ? ' multiple' : '').'><label for="'.${$string_type.'id'}.'" class="'.$prefix_id.$string_type.'_label">'.($this->getHeader()[$colnum] ?? '').'</label>';
                break;
            case 'color':
                #Attempting to sanitize the value provided allowing only 0-9 and a-f characters and padding from left to 6 characters
                $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefix_id.'_color" type="color" value="#'.mb_str_pad(mb_substr(\preg_replace(self::$color_hex_regex, '', $string), 0, 6, 'UTF-8'), 6, '0', \STR_PAD_LEFT, 'UTF-8').'"'.($this->getEditable() && !$footer ? '' : ' disabled').' pattern="^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$"><label for="'.${$string_type.'id'}.'" class="'.$prefix_id.$string_type.'_label">'.($this->getHeader()[$colnum] ?? '').'</label>';
                break;
        }
        if ((!$this->getEditable() || $footer) && in_array($string_type, ['date', 'time', 'datetime', 'seconds', 'bytes', 'price', 'text', 'tel', 'password', 'textarea'])) {
            $string = '<span id="'.${$string_type.'id'}.'" class="'.$prefix_id.$string_type.'">'.$string.'</span>';
        }
        return $string;
    }
    
    #####################
    #Setters and getters#
    #####################
    /**
     * Get current array of header titles
     * @return array
     */
    public function getHeader(): array
    {
        return $this->header;
    }
    
    /**
     * Set array of titles to be used for <thead> element
     * @param array $header
     *
     * @return $this
     */
    public function setHeader(array $header = []): self
    {
        $this->header = \array_values($header);
        return $this;
    }
    
    /**
     * Get current array of footer titles
     * @return array
     */
    public function getFooter(): array
    {
        return $this->footer;
    }
    
    /**
     * Set array of titles to be used for <tfoot> element.
     * Supports functions for columns with singular data type:
     * - '#func_sum' (sum of all values in column)
     * - '#func_avg' (average of all values in column)
     * - '#func_min' (lowest value in column)
     * - '#func_max' (maximum value in column)
     *
     * @param array $footer
     *
     * @return $this
     */
    public function setFooter(array $footer = []): self
    {
        $this->footer = \array_values($footer);
        return $this;
    }
    
    
    /**
     * Get the current value of header repeat
     * @return int
     */
    public function getRepeatHeader(): int
    {
        return $this->repeat_header;
    }
    
    /**
     * Enable repeating the header after each $repeat_header rows
     * @param int $repeat_header Number of rows after each header needs to be repeated
     *
     * @return $this
     */
    public function setRepeatHeader(int $repeat_header = 0): self
    {
        $this->repeat_header = $repeat_header;
        return $this;
    }
    
    /**
     * Check if we are using header titles in footer
     * @return bool
     */
    public function getFooterHeader(): bool
    {
        return $this->footer_header;
    }
    
    /**
     * Enable or disable use of header titles in footer
     * @param bool $footer_header
     *
     * @return $this
     */
    public function setFooterHeader(bool $footer_header = false): self
    {
        $this->footer_header = $footer_header;
        return $this;
    }
    
    /**
     * Get current text for caption
     * @return string
     */
    public function getCaption(): string
    {
        return $this->caption;
    }
    
    /**
     * Set text to use in `<caption>` element
     * @param string $caption
     *
     * @return $this
     */
    public function setCaption(string $caption = ''): self
    {
        $this->caption = $caption;
        return $this;
    }
    
    /**
     * Get current types defined for columns
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }
    
    /**
     * Set specific data types per each column in the table. This will define the way each column will be styled.
     * @param array $types
     *
     * @return $this
     */
    public function setTypes(array $types = []): self
    {
        $types = \array_values($types);
        $this->types = $types;
        return $this;
    }
    
    /**
     * Get current definition of column span
     * @return array
     */
    public function getColGroup(): array
    {
        return $this->colgroup;
    }
    
    /**
     * Define column span. Expects array like [['span'=>2,'class'=>'col_class','style'=>'col_style'],[...],...,[...]].
     * 'span' expect a numeric value identifying number of columns in a group.
     * 'class' expects a class that will be additionally applied to the group.
     * 'style' expects a CSS style string that will be additionally applied to group.
     *
     * @param array $colgroup
     *
     * @return $this
     */
    public function setColGroup(array $colgroup = []): self
    {
        $colgroup = \array_values($colgroup);
        foreach ($colgroup as $key => $group) {
            if (empty($group['span'])) {
                $colgroup[$key]['span'] = 1;
            } elseif (\is_numeric($group['span'])) {
                $colgroup[$key]['span'] = (int)$group['span'];
            } else {
                throw new \UnexpectedValueException('Types were sent, but have different length than array.');
            }
            if (!empty($group['class'])) {
                if (is_array($group['class']) || is_object($group['class'])) {
                    throw new \UnexpectedValueException('Colgroup class provided cannot be cast to string.');
                }
                $colgroup[$key]['class'] = (string)$group['class'];
            }
            if (!empty($group['style'])) {
                if (is_array($group['style']) || is_object($group['style'])) {
                    throw new \UnexpectedValueException('Colgroup style provided cannot be cast to string.');
                }
                $colgroup[$key]['style'] = (string)$group['style'];
            }
            $this->groups_count += $colgroup[$key]['span'];
        }
        $this->colgroup = $colgroup;
        return $this;
    }
    
    /**
     * Check if semantic output is enabled
     * @return bool
     */
    public function getSemantic(): bool
    {
        return $this->semantic;
    }
    
    /**
     * Enable or disable semantic output. If disabled `<div>` elements will be used instead of `<table>`, `<tr>`, `<td>`, `<th>` and so on.
     * @param bool $semantic
     *
     * @return $this
     */
    public function setSemantic(bool $semantic = true): self
    {
        $this->semantic = $semantic;
        return $this;
    }
    
    /**
     * Check if inline styling is forced for non-semantic table
     * @return bool
     */
    public function getStyling(): bool
    {
        return $this->styling;
    }
    
    /**
     * Force basic inline styling for <div> variant. Custom styling is recommended.
     * @param bool $styling
     *
     * @return $this
     */
    public function setStyling(bool $styling = false): self
    {
        $this->styling = $styling;
        return $this;
    }
    
    /**
     * Check if table is editable
     * @return bool
     */
    public function getEditable(): bool
    {
        return $this->editable;
    }
    
    /**
     * Allow or disallow editing of the table fields once generated
     * @param bool $editable
     *
     * @return $this
     */
    public function setEditable(bool $editable = false): self
    {
        $this->editable = $editable;
        return $this;
    }
    
    /**
     * Check if checkbox column is enabled or not
     * @return bool
     */
    public function getCheckbox(): bool
    {
        return $this->checkbox;
    }
    
    /**
     * Generate with first column being a checkbox with unique ID if true. Works for multi-arrays only.
     * @param bool $checkbox
     *
     * @return $this
     */
    public function setCheckbox(bool $checkbox = false): self
    {
        $this->checkbox = $checkbox;
        return $this;
    }
    
    /**
     * Check if checkbox column is generated as checked or not
     * @return bool
     */
    public function getChecked(): bool
    {
        return $this->checked;
    }
    
    /**
     * Generate checkbox column with checked box, if true
     * @param bool $checked
     *
     * @return $this
     */
    public function setChecked(bool $checked = false): self
    {
        $this->checked = $checked;
        return $this;
    }
    
    /**
     * Check if sanitization is enabled
     * @return bool
     */
    public function getSanitize(): bool
    {
        return $this->sanitize;
    }
    
    /**
     * Attempt to strip tags and encode HTML entities, unless html data type, if set to true.
     * @param bool $sanitize
     *
     * @return $this
     */
    public function setSanitize(bool $sanitize = true): self
    {
        $this->sanitize = $sanitize;
        return $this;
    }
    
    /**
     * Check if multiple files area allowed for file inputs
     * @return bool
     */
    public function getMultipleFiles(): bool
    {
        return $this->multiple_files;
    }
    
    /**
     * Allow or disallow multiple files selection for file inputs
     * @param bool $multiple_files
     *
     * @return $this
     */
    public function setMultipleFiles(bool $multiple_files = false): self
    {
        $this->multiple_files = $multiple_files;
        return $this;
    }
    
    /**
     * Get current prefix for IDs
     * @return string
     */
    public function getIdPrefix(): string
    {
        return $this->id_prefix;
    }
    
    /**
     * Set custom prefix for IDs of elements
     *
     * @param string $id_prefix
     *
     * @return $this
     */
    public function setIdPrefix(string $id_prefix = 'simbiat'): self
    {
        if (!empty($id_prefix) && !str_ends_with($id_prefix, '_')) {
            $id_prefix .= '_';
        }
        $this->id_prefix = $id_prefix;
        return $this;
    }
    
    /**
     * Get current settings for price
     *
     * @return array
     */
    public function getCurrencySettings(): array
    {
        return $this->currency_settings;
    }
    
    /**
     * Update settings for price elements
     *
     * @param string $code      Currency code (or symbol) for price type. Add space at the end to put it before the value.
     * @param int    $precision Precision of floating point (number of digits after the dot)
     * @param bool   $fraction  Treat values as fractions (like cents in case of USD) if `true` or treat values as floats if `false`
     *
     * @return $this
     */
    public function setCurrencySettings(string $code = '', int $precision = 2, bool $fraction = true): self
    {
        $this->currency_settings = \compact('code', 'precision', 'fraction');
        return $this;
    }
    
    /**
     * Get current count of a certain type
     * @param string $type
     *
     * @return string
     */
    private function getCounter(string $type): string
    {
        $cur_count = $this->{$type.'_count'};
        $this->setCounter($type, $cur_count + 1);
        return (string)$cur_count;
    }
    
    /**
     * Set the value of the counter for a certain type
     * @param string $type  Type to update
     * @param int    $value Value to set
     *
     * @return self
     */
    private function setCounter(string $type, int $value): self
    {
        $this->{$type.'_count'} = $value;
        return $this;
    }
    
    /**
     * Update settings for textarea elements
     *
     * @param int $rows      Rows for textarea (affects height of the textarea)
     * @param int $cols      Columns for textarea (affects width of the textarea)
     * @param int $minlength Minimum length of the data allowed in textarea
     * @param int $maxlength Maximum length of the data allowed in textarea
     *
     * @return $this
     */
    public function setTextareaSetting(int $rows = 20, int $cols = 20, int $minlength = 0, int $maxlength = 0): self
    {
        if ($rows < 1) {
            $rows = 20;
        }
        if ($cols < 1) {
            $cols = 20;
        }
        if ($minlength < 0) {
            $minlength = 0;
        }
        if ($maxlength < 0) {
            $maxlength = 0;
        }
        $this->textarea_settings = \compact('rows', 'cols', 'minlength', 'maxlength');
        return $this;
    }
}
