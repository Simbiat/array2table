<?php
# Suppressing inspection for elements counters, since they are access as variable variables
/** @noinspection PhpUnusedPrivateFieldInspection */
declare(strict_types = 1);

namespace Simbiat;

use JetBrains\PhpStorm\Pure;
use Simbiat\HTTP20\PrettyURL;
use function count, is_array, in_array, is_object;

/**
 * Generate HTML table from array
 */
class array2table
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
    private bool $multiFlag = false;
    #Flag to allow edit of the editable fields. Disabled by default
    private bool $editable = false;
    #Optional caption for tables
    private string $caption = '';
    #Optional header to be used
    private array $header = [];
    #Option to repeat header every x lines
    private int $repeatHeader = 0;
    #Optional footer to be used
    private array $footer = [];
    #Use header text in footer. Disabled by default
    private bool $footerHeader = false;
    #Optional column groups
    private array $colgroup = [];
    #Temporary count of groups for validation purpose
    private int $groupsCount = 0;
    #Optional types, that allow additional formatting of text
    private array $types = [];
    #Optional prefix for elements' IDs and some classes
    private string $idPrefix = 'simbiat';
    #Option to allow multiple files upload for file fields. Disabled by default
    private bool $multipleFiles = false;
    
    #Partner libraries flags
    private static bool $CuteBytes = false;
    private static bool $SandClock = false;
    private static bool $PrettyURL = false;
    
    #Regex patterns for validations
    public static string $URIRegex = '/^(about|afp|aim|bitcoin|callto|chrome|chrome-extension|content|dns|ed2k|facetime|fax|feed|file|ftp|geo|git|hcp|http|https|im|imap|info|irc|irc6|ircs|itms|jabber|lastfm|ldap|ldaps|magnet|maps|market|message|mms|ms-help|msnim|mumble|nfs|oid|pkcs11|pop|proxy|res|rtmfp|rtmp|rtsp|sftp|shttp|sip|sips|skype|smb|sms|soldat|spotify|ssh|steam|svn|teamspeak|tel|telnet|tftp|tv|udp|unreal|urn|ventrilo|webcal|xfire|xmpp):\/\/(-\.)?([^\s\/?\.#-]+\.?)+(\/[^\s]*)?$/i';
    #Uses W3C pattern for compatibility with input type 'email'. Does not imply that all matching strings will be actual proper emails
    public static string $eMailRegex = '/^[a-zA-Z0-9.!#$%&’*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/';
    public static string $ColorHexRegex = '/[^a-fA-F0-9]/m';
    #While this static value can be changed, it is not recommended, unless you are ready to decrease your system security
    public static string $PasswordPattern = '(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$';
    
    #Formatting settings
    private string $dateformat = 'Y-m-d';
    private string $timeFormat = 'H:i:s';
    private string $dateTimeFormat = '';
    private string $language = 'en';
    #Set an optional currency code (or symbol) for 'price' type. Add space at the end to put it before the value
    private string $currencyCode = '';
    #Set precision of floating point for 'price' type
    private int $currencyPrecision = 2;
    #Set to 'false' to treat values for 'price' type as floats. Treat values as fractions (like cents in case of USD) by default
    private bool $currencyFraction = true;
    #Set default values for 'textarea' type
    private array $textareaSettings = [
        'rows' => '20',
        'cols' => '2',
        'minlength' => '',
        'maxlength' => '',
    ];
    
    #Counters for elements
    private int $checkboxCount = 1;
    private int $emailCount = 1;
    private int $urlCount = 1;
    private int $textareaCount = 1;
    private int $fileCount = 1;
    private int $colorCount = 1;
    private int $textCount = 1;
    private int $telCount = 1;
    private int $passwordCount = 1;
    private int $imgCount = 1;
    private int $priceCount = 1;
    private int $bytesCount = 1;
    private int $secondsCount = 1;
    private int $datetimeCount = 1;
    private int $timeCount = 1;
    private int $dateCount = 1;
    private int $htmlCount = 1;
    
    /**
     * Generate the table
     * @throws \Exception
     */
    public function generate(array $array): string
    {
        if (!self::$CuteBytes && method_exists(CuteBytes::class, 'bytes')) {
            self::$CuteBytes = true;
        }
        if (!self::$SandClock && method_exists(SandClock::class, 'format')) {
            self::$SandClock = true;
        }
        if (!self::$PrettyURL && method_exists(PrettyURL::class, 'pretty')) {
            self::$PrettyURL = true;
        }
        if (empty($array)) {
            throw new \UnexpectedValueException('Empty array provided.');
        }
        #Get length
        if (ArrayHelpers::multi($array, true, true)) {
            $this->multiFlag = true;
            $array = array_values($array);
            $length = array_unique(array_map('\count', $array))[0];
        } else {
            $length = count($array);
        }
        #Check if header have same length
        if (!empty($this->getHeader()) && count($this->getHeader()) !== $length) {
            throw new \UnexpectedValueException('Header was sent, but has different length than array.');
        }
        #Check if footer have same length
        if (!empty($this->getFooter()) && count($this->getFooter()) !== $length) {
            throw new \UnexpectedValueException('Footer was sent, but has different length than array.');
        }
        #Check if types have same length
        if (empty($this->getTypes())) {
            #Filling the types' array with 'string' value. Using 'string' so that all elements would be converted to regular <span> elements as a safety precaution
            $this->setTypes(array_fill(0, $length, 'string'));
        } elseif (count($this->getTypes()) !== $length) {
            throw new \UnexpectedValueException('Types were sent, but have different length than array.');
        }
        #Check if colgroup list has same length
        if ($this->groupsCount !== 0 && $this->groupsCount !== $length) {
            throw new \UnexpectedValueException('Column groups were sent, but have different length than array.');
        }
        #Disable repeat header if it's equal to length
        if ($this->getRepeatHeader() === $length) {
            $this->setRepeatHeader(0);
        }
        #Set header
        if ($this->multiFlag) {
            $array = array_values($array);
            if (!empty($array[0]) && ArrayHelpers::isAssociative($array[0]) && empty($this->getHeader())) {
                $this->setHeader(array_keys($array[0]));
            }
            #Convert data's associative array to regular one for consistency
            $array = array_values($array);
            foreach ($array as $row => $rowData) {
                $array[$row] = array_values($rowData);
            }
        } else {
            if (ArrayHelpers::isAssociative($array) && empty($this->getHeader())) {
                $this->setHeader(array_keys($array));
            }
            #Convert data's associative array to regular one for consistency
            $array = array_values($array);
        }
        #Check if types for each column has same length as actual column data
        $tempTypes = array_values($this->getTypes());
        foreach ($tempTypes as $column => $typeList) {
            if (is_array($typeList)) {
                #Since we are parsing the array either way, convert associative array to regular one for consistency
                $tempTypes[$column] = array_values($typeList);
                if ($this->multiFlag) {
                    if (count($typeList) !== count($array)) {
                        throw new \UnexpectedValueException('Multi-row types\' column '.$column.' have different length than array.');
                    }
                } else {
                    throw new \UnexpectedValueException('Multi-row types were sent, but data is made up of one row.');
                }
            }
        }
        #Convert types' associative array to regular one for consistency
        $this->setTypes(array_values($tempTypes));
        #Convert footer associative array to regular one for consistency
        $this->setFooter(array_values($this->getFooter()));
        #Checking if footer is a function and updating value accordingly
        if (!empty($this->getFooter())) {
            $tempFooter = array_values($this->getFooter());
            foreach ($tempFooter as $column => $footer) {
                if (in_array($footer, ['#func_sum', '#func_avg', '#func_min', '#func_max'])) {
                    if ($this->multiFlag) {
                        if (is_array($this->getTypes()[$column])) {
                            throw new \UnexpectedValueException('Footer function was sent for column '.$column.', but column has multiple types assigned to it.');
                        }
                        $columnData = array_column($array, $column);
                        $tempFooter[$column] = match ($footer) {
                            '#func_sum' => 'Sum: '.$this->prepare(array_sum($columnData), $column, 0, true),
                            '#func_avg' => 'Avg: '.$this->prepare(array_sum($columnData) / $length, $column, 0, true),
                            '#func_min' => 'Min: '.$this->prepare(min($columnData), $column, 0, true),
                            '#func_max' => 'Max: '.$this->prepare(max($columnData), $column, 0, true),
                            default => '',
                        };
                    } else {
                        throw new \UnexpectedValueException('Footer function was sent for column '.$column.', but data is made up of one row.');
                    }
                }
            }
            $this->setFooter($tempFooter);
        }
        return $this->table($array);
    }
    
    /**
     * Resetting values. Useful, in case, someone decides to use same object several times
     * @throws \Exception
     */
    public function default(): self
    {
        $this->setHeader([])->setFooter([])->setColGroup([])->setTypes([])->setSemantic(true)->setEditable(false)->setCheckbox(false)->setChecked(false)->setSanitize(true)->setCaption('')->setRepeatHeader(0)->setIdPrefix('simbiat')->setCounter('checkbox', 1)->setCounter('email', 1)->setCounter('url', 1)->setCounter('textarea', 1)->setCounter('file', 1)->setCounter('text', 1)->setCounter('tel', 1)->setCounter('password', 1)->setCounter('img', 1)->setCounter('price', 1)->setCounter('bytes', 1)->setCounter('seconds', 1)->setCounter('datetime', 1)->setCounter('time', 1)->setCounter('date', 1)->setCounter('html', 1)->setMultipleFiles(false)->setCurrencyCode('')->setCurrencyPrecision(2)->setCurrencyFraction(true);
        return $this;
    }
    
    /**
     * @throws \Exception
     */
    private function table(array $array): string
    {
        #Get prefix
        $prefixId = $this->getIdPrefix();
        $table = '<'.($this->getSemantic() ? 'table' : 'div'.($this->getStyling() ? ' style="display:table;border-spacing:2px;"' : '')).' id="'.$prefixId.'table">';
        #Set caption
        if (!empty($this->getCaption())) {
            $table .= '<'.($this->getSemantic() ? 'caption' : 'div'.($this->getStyling() ? ' style="display:table-caption;text-align:center;"' : '')).' id="'.$prefixId.'caption">'.$this->getCaption().'</'.($this->getSemantic() ? 'caption' : 'div').'>';
        }
        #Set colgroup value
        if ($this->groupsCount !== 0 && !empty($this->getColGroup())) {
            $table .= '<'.($this->getSemantic() ? 'colgroup' : 'div'.($this->getStyling() ? ' style="display:table-column-group;"' : '')).' id="'.$prefixId.'colgroup">';
            foreach ($this->getColGroup() as $key => $group) {
                $table .= '<'.($this->getSemantic() ? 'col' : 'div'.($this->getStyling() ? ' style="display:table-column;"' : '')).'  id="'.$prefixId.'col_'.$key.'" span="'.$group['span'].'"';
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
            $table .= '<'.($this->getSemantic() ? 'thead' : 'div'.($this->getStyling() ? ' style="display:table-header-group;font-weight:bold;text-align:center;"' : '')).' class="'.$prefixId.'thead"><'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).'>';
            #Add checkbox column (empty for header)
            $table .= $this->checkboxColumn($prefixId);
            $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'></'.($this->getSemantic() ? 'thead' : 'div').'>';
        }
        $table .= '<'.($this->getSemantic() ? 'tbody' : 'div'.($this->getStyling() ? ' style="display:table-row-group;"' : '')).' id="'.$prefixId.'tbody">';
        #Check if array of array
        if ($this->multiFlag) {
            foreach ($array as $row => $subarray) {
                if ($row > 0 && $this->getRepeatHeader() !== 0 && !empty($this->getHeader()) && fmod($row + 1, $this->getRepeatHeader()) === 0.0) {
                    $table .= '<'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).'>';
                    #Add checkbox column (empty for header)
                    $table .= $this->checkboxColumn($prefixId);
                    $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'>';
                }
                $table .= '<'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).' id="'.$prefixId.'tr_'.$row.'">';
                #Add checkbox column
                if ($this->getCheckbox()) {
                    $table .= '<'.($this->getSemantic() ? 'td' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' class="'.$prefixId.'checkbox_field" id="'.$prefixId.'td_checkbox_'.$row.'"><input type="checkbox" id="'.$prefixId.'checkbox_'.$row.'"'.($this->getChecked() ? ' checked' : '').'></'.($this->getSemantic() ? 'td' : 'div').'>';
                }
                $subarray = array_values($subarray);
                foreach ($subarray as $column => $cell) {
                    $table .= '<'.($this->getSemantic() ? 'td' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' id="'.$prefixId.'td_'.$row.'_'.$column.'">'.$this->prepare($cell, $column, $row).'</'.($this->getSemantic() ? 'td' : 'div').'>';
                }
                $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'>';
            }
        } else {
            $table .= '<'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).' id="'.$prefixId.'tr_0">';
            $array = array_values($array);
            foreach ($array as $column => $cell) {
                $table .= '<'.($this->getSemantic() ? 'td' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' id="'.$prefixId.'td_0_'.$column.'">'.$this->prepare($cell, $column).'</'.($this->getSemantic() ? 'td' : 'div').'>';
            }
            $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'>';
        }
        $table .= '</'.($this->getSemantic() ? 'tbody' : 'div').'>';
        #Set footer
        if (!empty($this->getFooter())) {
            $table .= '<'.($this->getSemantic() ? 'tfoot' : 'div'.($this->getStyling() ? ' style="display:table-footer-group;font-weight:bold;text-align:center;"' : '')).' id="'.$prefixId.'tfoot"><'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).'>';
            #Add checkbox column (empty for footer)
            if ($this->getCheckbox()) {
                $table .= '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' class="'.$prefixId.'checkbox_dummy_field"></'.($this->getSemantic() ? 'th' : 'div').'>';
            }
            foreach ($this->getFooter() as $key => $footer) {
                $table .= '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' class="'.$prefixId.'th_'.$key.'">'.$footer.'</'.($this->getSemantic() ? 'th' : 'div').'>';
            }
            $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'></'.($this->getSemantic() ? 'tfoot' : 'div').'>';
        } elseif ($this->getFooterHeader() && !empty($this->getHeader())) {
            $table .= '<'.($this->getSemantic() ? 'tfoot' : 'div'.($this->getStyling() ? ' style="display:table-footer-group;font-weight:bold;text-align:center;"' : '')).' id="'.$prefixId.'tfoot"><'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).'>';
            #Add checkbox column (empty for footer)
            $table .= $this->checkboxColumn($prefixId);
            $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'></'.($this->getSemantic() ? 'tfoot' : 'div').'>';
        }
        $table .= '</'.($this->getSemantic() ? 'table' : 'div').'>';
        return $table;
    }
    
    /**
     * Function to handle checkbox columns
     * @param string $prefixId
     *
     * @return string
     */
    #[Pure] private function checkboxColumn(string $prefixId): string
    {
        $column = '';
        if ($this->getCheckbox()) {
            $column .= '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' class="'.$prefixId.'checkbox_dummy_field"></'.($this->getSemantic() ? 'th' : 'div').'>';
        }
        foreach ($this->getHeader() as $key => $header) {
            $column .= '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;padding:1px;vertical-align: middle;"' : '')).' class="'.$prefixId.'th_'.$key.'">'.$header.'</'.($this->getSemantic() ? 'th' : 'div').'>';
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
        $prefixId = $this->getIdPrefix();
        #Set ID for the element
        if (in_array($string_type, ['html', 'date', 'time', 'datetime', 'seconds', 'bytes', 'price', 'checkbox', 'email', 'url', 'textarea', 'text', 'tel', 'password', 'img', 'file', 'color'])) {
            if ($footer) {
                ${$string_type.'id'} = $prefixId.'footer_'.$colnum;
            } else {
                ${$string_type.'id'} = $prefixId.$string_type.'_'.$this->getCounter($string_type);
            }
        }
        if ($string_type !== 'html' && $this->getSanitize()) {
            $string = strip_tags($string);
        }
        switch ($string_type) {
            case 'date':
                if ($footer === false && $this->getEditable()) {
                    if (self::$SandClock) {
                        $string = SandClock::format($string, 'Y-m-d');
                    }
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixId.'_'.$string_type.'" type="date" step="1" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="'.$string.'">';
                } else {
                    if (self::$SandClock) {
                        $string = SandClock::format($string, $this->getDateFormat());
                    }
                    $string = '<time datetime="'.(new \DateTime($string))->format('Y-m-d').'">'.$string.'</time>';
                }
                break;
            case 'time':
                if ($footer === false && $this->getEditable()) {
                    if (self::$SandClock) {
                        $string = SandClock::format($string, 'H:i:s');
                    }
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixId.'_'.$string_type.'" type="time" step="1" pattern="[0-9]{2}:[0-9]{2}:[0-9]{2}" value="'.$string.'">';
                } else {
                    if (self::$SandClock) {
                        $string = SandClock::format($string, $this->getTimeFormat());
                    }
                    $string = '<time datetime="'.(new \DateTime($string))->format('H:i:s').'">'.$string.'</time>';
                }
                break;
            case 'datetime':
                if ($footer === false && $this->getEditable()) {
                    if (self::$SandClock) {
                        $string = SandClock::format($string, 'Y-m-d\TH:i:s');
                    }
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixId.'_'.$string_type.'" type="datetime-local" step="1" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}" value="'.$string.'">';
                } else {
                    if (self::$SandClock) {
                        $string = SandClock::format($string, $this->getDateTimeFormat());
                    }
                    $string = '<time datetime="'.(new \DateTime($string))->format('Y-m-dTH:i:s.u').'">'.$string.'</time>';
                }
                break;
            case 'seconds':
            case 'bytes':
                if ($footer === false && $this->getEditable()) {
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixId.'_'.$string_type.'" type="number" step="1" min="0" inputmode="decimal" value="'.$string.'">';
                } elseif ($string_type === 'bytes' && self::$CuteBytes) {
                    $string = CuteBytes::bytes($string);
                } elseif (self::$SandClock) {
                    $string = SandClock::seconds($string, true, $this->getLanguage());
                }
                break;
            case 'price':
                #Expects integer string, where last 2 numbers are the "fractions" (like cents)
                if ($this->getCurrencyFraction()) {
                    $string = substr_replace((string)(int)$string, '.', -$this->getCurrencyPrecision(), 0);
                    if (str_starts_with($string, '.')) {
                        $string = '0'.$string;
                    }
                } else {
                    $string = number_format((float)$string, $this->getCurrencyPrecision(), '.', '');
                }
                if ((!$this->getEditable() || $footer === true) && !empty($this->getCurrencyCode())) {
                    #If code ends with space - place it before the value
                    if (str_ends_with($this->getCurrencyCode(), ' ')) {
                        $string = $this->getCurrencyCode().$string;
                    } else {
                        $string .= ' '.$this->getCurrencyCode();
                    }
                }
                if ($footer === false && $this->getEditable()) {
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixId.'_'.$string_type.'" type="number" step="0.01" min="0.00" inputmode="decimal" value="'.$string.'">';
                }
                break;
            case 'checkbox':
                #Sanitizing values
                if (preg_match('/^on|yes$/mi', $string) === 1) {
                    $checkboxStatus = ' checked';
                } elseif (preg_match('/^off|no$/mi', $string) === 1) {
                    $checkboxStatus = '';
                } elseif ((bool)$string === true) {
                    $checkboxStatus = ' checked';
                } else {
                    $checkboxStatus = '';
                }
                $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixId.'_checkbox" type="checkbox"'.$checkboxStatus.($this->getEditable() && $footer === false ? '' : ' disabled').'>';
                break;
            case 'email':
            case 'url':
                #Processes string only if validates as actual URI/URL or e-mail
                if (preg_match(($string_type === 'url' ? self::$URIRegex : self::$eMailRegex), $string)) {
                    if ($string_type === 'url' && self::$PrettyURL) {
                        $string = PrettyURL::pretty($string, urlSafe: $this->getSanitize());
                    }
                    if ($footer === false && $this->getEditable()) {
                        $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixId.'_'.$string_type.'" type="'.$string_type.'" inputmode="'.$string_type.'" value="'.$string.'">';
                    } else {
                        $string = '<a id="'.${$string_type.'id'}.'" class="'.$prefixId.'_'.$string_type.'" href="'.($string_type === 'url' ? '' : 'mailto:').$string.'" '.($string_type === 'url' ? 'target="_blank"' : '').'>'.$string.'</a>';
                    }
                } else {
                    $string = '<span id="'.${$string_type.'id'}.'" class="'.$prefixId.'_'.$string_type.'" type="'.$string_type.'">'.$string.'</span>';
                }
                break;
            case 'html':
                #If editable, treat as textarea
                if ($footer === true || !$this->getEditable()) {
                    $string = htmlentities($string, ENT_QUOTES | ENT_HTML5 | ENT_DISALLOWED);
                    break;
                }
                break;
            case 'textarea':
                if ($footer === false && $this->getEditable()) {
                    $string = '<textarea id="'.${$string_type.'id'}.'" class="'.$prefixId.'_'.$string_type.'" cols="'.$this->getTextareaSetting('cols').'" rows="'.$this->getTextareaSetting('rows').'" minlength="'.$this->getTextareaSetting('minlength').'" maxlength="'.$this->getTextareaSetting('maxlength').'" spellcheck="true">'.$string.'</textarea>';
                }
                break;
            case 'text':
            case 'tel':
            case 'password':
                #Removing the entry, in case actual password is sent
                if ($string_type === 'password') {
                    $string = '';
                }
                if ($footer === false && $this->getEditable()) {
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixId.'_'.$string_type.'" type="'.$string_type.'" inputmode="'.$string_type.'" value="'.$string.'"'.($string_type === 'password' ? 'pattern="'.self::$PasswordPattern.'"' : '').'>';
                }
                break;
            case 'img':
                #If editable, treat as input="file"
                if ($footer === true || !$this->getEditable()) {
                    #alt is set as "" to make some browsers consider images as non-essential. If an image needs to be considered as essential it's recommended not to show it through this library. Alternatively you can update it through JavaScript or other programmatic methods.
                    $string = '<img loading="lazy" src="'.$string.'" alt="" decoding="async" class="'.$prefixId.'_'.$string_type.'">';
                    break;
                }
                break;
            case 'file':
                $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixId.'_'.$string_type.'" type="file"'.($this->getEditable() && $footer === false ? '' : ' disabled').($this->getMultipleFiles() ? ' multiple' : '').'>';
                break;
            case 'color':
                #Attempting to sanitize the value provided allowing only 0-9 and a-f characters and padding from left to 6 characters
                $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixId.'_color" type="color" value="#'.mb_str_pad(mb_substr(preg_replace(self::$ColorHexRegex, '', $string), 0, 6, 'UTF-8'), 6, '0', STR_PAD_LEFT, 'UTF-8').'"'.($this->getEditable() && $footer === false ? '' : ' disabled').' pattern="^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$">';
                break;
        }
        if ((!$this->getEditable() || $footer === true) && in_array($string_type, ['date', 'time', 'datetime', 'seconds', 'bytes', 'price', 'text', 'tel', 'password', 'textarea'])) {
            $string = '<span id="'.${$string_type.'id'}.'" class="'.$prefixId.'_'.$string_type.'">'.$string.'</span>';
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
    public function setHeader(array $header): self
    {
        $this->header = array_values($header);
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
    public function setFooter(array $footer): self
    {
        $this->footer = array_values($footer);
        return $this;
    }
    
    
    /**
     * Get current value of header repeat
     * @return int
     */
    public function getRepeatHeader(): int
    {
        return $this->repeatHeader;
    }
    
    /**
     * Enable repeating the header after each $repeatHeader rows
     * @param int $repeatHeader Number of rows after each header needs to be repeated
     *
     * @return $this
     */
    public function setRepeatHeader(int $repeatHeader): self
    {
        $this->repeatHeader = $repeatHeader;
        return $this;
    }
    
    /**
     * Check if we are using header titles in footer
     * @return bool
     */
    public function getFooterHeader(): bool
    {
        return $this->footerHeader;
    }
    
    /**
     * Enable or disable use of header titles in footer
     * @param bool $footerHeader
     *
     * @return $this
     */
    public function setFooterHeader(bool $footerHeader): self
    {
        $this->footerHeader = $footerHeader;
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
     * Set text to use in <caption> element
     * @param string $caption
     *
     * @return $this
     */
    public function setCaption(string $caption): self
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
    public function setTypes(array $types): self
    {
        $types = array_values($types);
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
    public function setColGroup(array $colgroup): self
    {
        $colgroup = array_values($colgroup);
        foreach ($colgroup as $key => $group) {
            if (empty($group['span'])) {
                $colgroup[$key]['span'] = 1;
            } elseif (is_numeric($group['span'])) {
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
            $this->groupsCount += $colgroup[$key]['span'];
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
     * Enable or disable semantic output. If disabled <div> elements will be used instead of <table>, <tr>, <td>, <th> and so on.
     * @param bool $semantic
     *
     * @return $this
     */
    public function setSemantic(bool $semantic): self
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
    public function setStyling(bool $styling): self
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
    public function setEditable(bool $editable): self
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
    public function setCheckbox(bool $checkbox): self
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
    public function setChecked(bool $checked): self
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
    public function setSanitize(bool $sanitize): self
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
        return $this->multipleFiles;
    }
    
    /**
     * Allow or disallow multiple files selection for file inputs
     * @param bool $multipleFiles
     *
     * @return $this
     */
    public function setMultipleFiles(bool $multipleFiles): self
    {
        $this->multipleFiles = $multipleFiles;
        return $this;
    }
    
    /**
     * Get current prefix for IDs
     * @return string
     */
    public function getIdPrefix(): string
    {
        return $this->idPrefix;
    }
    
    /**
     * Set custom prefix for IDs of elements
     * @param string $idPrefix
     *
     * @return $this
     */
    public function setIdPrefix(string $idPrefix): self
    {
        if (!empty($idPrefix) && !str_ends_with($idPrefix, '_')) {
            $idPrefix .= '_';
        }
        $this->idPrefix = $idPrefix;
        return $this;
    }
    
    /**
     * Get currently set date form
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateformat;
    }
    
    /**
     * Set custom date format for date fields
     * @param string $dateformat
     *
     * @return $this
     */
    public function setDateFormat(string $dateformat): self
    {
        $this->dateformat = $dateformat;
        return $this;
    }
    
    /**
     * Get current currency code
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }
    
    /**
     * Set currency code for price fields
     * @param string $currencyCode
     *
     * @return $this
     */
    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;
        return $this;
    }
    
    /**
     * Get current currency precision
     * @return int
     */
    public function getCurrencyPrecision(): int
    {
        return $this->currencyPrecision;
    }
    
    /**
     * Set currency precision for price fields
     * @param int $currencyPrecision
     *
     * @return $this
     */
    public function setCurrencyPrecision(int $currencyPrecision): self
    {
        $this->currencyPrecision = $currencyPrecision;
        return $this;
    }
    
    /**
     * Check if currency is treated as floats
     * @return bool
     */
    public function getCurrencyFraction(): bool
    {
        return $this->currencyFraction;
    }
    
    /**
     * Treat currency as floats, if set to true, meaning that cents and the like will be shown after a fraction mark.
     * @param bool $currencyFraction
     *
     * @return $this
     */
    public function setCurrencyFraction(bool $currencyFraction): self
    {
        $this->currencyFraction = $currencyFraction;
        return $this;
    }
    
    /**
     * Get current time format
     * @return string
     */
    public function getTimeFormat(): string
    {
        return $this->timeFormat;
    }
    
    /**
     * Set custom time format
     * @param string $timeFormat
     *
     * @return $this
     */
    public function setTimeFormat(string $timeFormat): self
    {
        $this->timeFormat = $timeFormat;
        return $this;
    }
    
    /**
     * Get current date and time format
     * @return string
     */
    #[Pure] public function getDateTimeFormat(): string
    {
        if (empty($this->dateTimeFormat)) {
            return $this->getDateFormat().' '.$this->getTimeFormat();
        }
        return $this->dateTimeFormat;
    }
    
    /**
     * Set custom date and time format
     * @param string $dateTimeFormat
     *
     * @return $this
     */
    public function setDateTimeFormat(string $dateTimeFormat): self
    {
        $this->dateTimeFormat = $dateTimeFormat;
        return $this;
    }
    
    /**
     * Get current language
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }
    
    /**
     * Language to use with `Simbiat\SandClock` library for `seconds` type.
     * @param string $language
     *
     * @return $this
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;
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
        $curCount = $this->{$type.'Count'};
        $this->setCounter($type, $curCount + 1);
        return (string)$curCount;
    }
    
    /**
     * Set value of the counter for a certain type
     * @param string $type  Type to update
     * @param int    $value Value to set
     *
     * @return self
     */
    private function setCounter(string $type, int $value): self
    {
        $this->{$type.'Count'} = $value;
        return $this;
    }
    
    /**
     * Get current settings for textarea elements
     * @param string $setting
     *
     * @return string
     */
    public function getTextareaSetting(string $setting): string
    {
        return $this->textareaSettings[$setting];
    }
    
    /**
     * Update settings for textarea elements
     * @param string $setting Setting to change
     * @param string $value   Setting's new value
     *
     * @return $this
     */
    public function setTextareaSetting(string $setting, string $value): self
    {
        if (!in_array($setting, ['rows', 'cols', 'minlength', 'maxlength'])) {
            throw new \UnexpectedValueException('Unsupported textarea setting provided. Only rows, cols, minlength, maxlength are supported.');
        }
        if ($value !== '' && !ctype_digit($value)) {
            throw new \UnexpectedValueException('Unsupported textarea setting value provided. Only integer string values and empty string are supported.');
        }
        $this->textareaSettings[$setting] = $value;
        return $this;
    }
}
