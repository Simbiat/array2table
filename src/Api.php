<?php
declare(strict_types=1);
namespace array2table;

class Api
{
    #Generate <table> if true or <div> if false
    private $semantic = true;
    #Force basic inline styling for <div> variant. Custom styling is recommended
    private $styling = false;
    #Generate with first column being a checkbox with unique ID if true. Works for multi-arrays only
    private $checkbox = false;
    #Generate with checkbox checked
    private $checked = false;
    #Whether to attempt to strip tags and encode HTML entities, if not enforcing HTML
    private $sanitize = true;
    #Flag for multidimensional arrays. Using for performance optimization
    private $multiflag = false;
    #Flag to allow edit of the editable fields. Disabled by default
    private $editable = false;
    #Optional caption for semantic tables
    private $caption = '';
    #Optional header to be used. Used only for semantic tables
    private $header = [];
    #Option to repeat header every x lines
    private $repeatheader = 0;
    #Optional footer to be used. Used only for semantic tables
    private $footer = [];
    #Use header text in footer. Disabled by default
    private $footerHeader = false;
    #Optional column groups. Used only for semantic tables
    private $colgroup = [];
    #Temporary count of groups for validation purpose
    private $groupscount = 0;
    #Optional types, that allow additional formatting of text
    private $types = [];
    #Optional prefix for elements' IDs (or classes in some cases)
    private $idprefix = 'simbiat';
    #Option to allow multiple files upload for file fields. Disabled by default
    private $multiplefiles = false;
    
    #Partner libraries flags
    private static $CuteBytes = false;
    private static $SandClock = false;
    private static $PrettyURL = false;
    
    #Regex patterns for validations
    public static $URIRegex = '/^(about|afp|aim|bitcoin|callto|chrome|chrome-extension|content|dns|ed2k|facetime|fax|feed|file|ftp|geo|git|hcp|http|https|im|imap|info|irc|irc6|ircs|itms|jabber|lastfm|ldap|ldaps|magnet|maps|market|message|mms|ms-help|msnim|mumble|nfs|oid|pkcs11|pop|proxy|res|rtmfp|rtmp|rtsp|sftp|shttp|sip|sips|slype|smb|sms|soldat|spotify|ssh|steam|svn|teamspeak|tel|telnet|tftp|tv|udp|unreal|urn|ventrilo|webcal|xfire|xmpp):\/\/(-\.)?([^\s\/?\.#-]+\.?)+(\/[^\s]*)?$/i';
    #Uses W3C pattern for compatibility with input type 'email'. Does not imply that all matching strings will be actual proper emails
    public static $eMailRegex = '/^[a-zA-Z0-9.!#$%&’*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/';
    public static $ColorHexRegex = '/[^a-fA-F0-9]/m';
    #While this static value can be changed, it is not recommended, unless you are ready to decrease your system security
    public static $PasswordPattern = '(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$';
    
    #Formatting settings
    private $dateformat = 'Y-m-d';
    private $timeformat = 'H:i:s';
    private $datetimeformat = '';
    private $language = 'en';
    #Set an optional currency code (or symbol) for 'price' type. Add space at the end to put it beofre the value
    private $currencyCode = '';
    #Set precission of floating point for 'price' type
    private $currencyPrecision = 2;
    #Set to 'false' to treat values for 'price' type as floats. Treat values as fractions (like cents in case of USD) by default
    private $currencyFraction = true;
    #Set default values for 'textarea' type
    private $textareaSettings = [
        'rows'=>'20',
        'cols'=>'2',
        'minlength'=>'',
        'maxlength'=>'',
    ];
    
    #Counters for elements
    private $countercheckbox = 1;
    private $counteremail = 1;
    private $counterurl = 1;
    private $countertextarea = 1;
    private $counterfile = 1;
    private $countercolor = 1;
    private $countertext = 1;
    private $countertel = 1;
    private $counterpassword = 1;
    private $counterimg = 1;
    private $counterprice = 1;
    private $counterbytes = 1;
    private $counterseconds = 1;
    private $counterdatetime = 1;
    private $countertime = 1;
    private $counterdate = 1;
    private $counterhtml = 1;
    
    public function generate(array $array): string
    {
        if (!self::$CuteBytes && method_exists('\CuteBytes\Api','bytes')) {
            self::$CuteBytes = true;
        }
        if (!self::$SandClock && method_exists('\SandClock\Api','format')) {
            self::$SandClock = true;
        }
        if (!self::$PrettyURL && method_exists('\PrettyURL\PrettyURL','pretty')) {
            self::$PrettyURL = true;
        }
        if (empty($array)) {
            throw new \UnexpectedValueException('Empty array provided.');
        }
        #Get length
        if ($this->multi($array)) {
            $array = array_values($array);
            $length = array_unique(array_map('count', $array))[0];
        } else {
            $length = count($array);
        }
        #Check if header have same length
        if (!empty($this->getHeader()) && count($this->getHeader()) != $length) {
            throw new \UnexpectedValueException('Header was sent, but has different length than array.');
        }
        #Check if footer have same length
        if (!empty($this->getFooter()) && count($this->getFooter()) != $length) {
            throw new \UnexpectedValueException('Footer was sent, but has different length than array.');
        }
        #Check if types have same length
        if (!empty($this->getTypes()) && count($this->getTypes()) != $length) {
            throw new \UnexpectedValueException('Types were sent, but have different length than array.');
        }
        #Check if colgroups has same length
        if ($this->groupscount != 0 && $this->groupscount != $length) {
            throw new \UnexpectedValueException('Column groups were sent, but have different length than array.');
        }
        #Disable repeat header if it's equal to length
        if ($this->getRepeatHeader() == $length) {
            $this->setRepeatHeader(0);
        }
        #Set header
        if ($this->multiflag) {
            $array = array_values($array);
            if (!empty($array[0])) {
                if ($this->assotiative($array[0]) && empty($this->getHeader())) {
                    $this->setHeader(array_keys($array[0]));
                }
            }
            #Convert data's assotiative array to regular one for consistency
            $array = array_values($array);
            foreach ($array as $row=>$rowdata) {
                $array[$row] = array_values($rowdata);
            }
        } else {
            if ($this->assotiative($array) && empty($this->getHeader())) {
                $this->setHeader(array_keys($array));
            }
            #Convert data's assotiative array to regular one for consistency
            $array = array_values($array);
        }
        #Check if types for each column has same length as actual column data
        $tempTypes = array_values($this->getTypes());
        foreach ($tempTypes as $column=>$typelist) {
            if (is_array($typelist)) {
                #Since we are parsing the array either way, convert assotiative array to regular one for consistency
                $tempTypes[$column] = array_values($typelist);
                if ($this->multiflag) {
                    if (count($typelist) !== count($array[$column])) {
                        throw new \UnexpectedValueException('Multi-row types\' column '.$column.' have different length than array.');
                    }
                } else {
                    throw new \UnexpectedValueException('Multi-row types were sent, but data is made up of one row.');
                }
            }
        }
        #Convert types' assotiative array to regular one for consistency
        $this->setTypes(array_values($tempTypes));
        #Convert footer assotiative array to regular one for consistency
        $this->setFooter(array_values($this->getFooter()));
        #Checking if footer is a function and updating value accordingly
        if (!empty($this->getFooter())) {
            $tempFooter = array_values($this->getFooter());
            foreach ($tempFooter as $column=>$footer) {
                if (in_array($footer, ['#func_sum', '#func_avg', '#func_min', '#func_max'])) {
                    if ($this->multiflag) {
                        if (!is_array($this->getTypes()[$column])) {
                            $columnData = array_column($array, $column);
                            switch ($footer) {
                                case '#func_sum':
                                    $tempFooter[$column] = 'Sum: '.$this->prepare(array_sum($columnData), $column, 0, true);
                                    break;
                                case '#func_avg':
                                    $tempFooter[$column] = 'Avg: '.$this->prepare(array_sum($columnData)/$length, $column, 0, true);
                                    break;
                                case '#func_min':
                                    $tempFooter[$column] = 'Min: '.$this->prepare(min($columnData), $column, 0, true);
                                    break;
                                case '#func_max':
                                    $tempFooter[$column] = 'Max: '.$this->prepare(max($columnData), $column, 0, true);
                                    break;
                            }
                        } else {
                            throw new \UnexpectedValueException('Footer function was sent for column '.$column.', but column has multiple types assigned to it.');
                        }
                    } else {
                        throw new \UnexpectedValueException('Footer function was sent for column '.$column.', but data is made up of one row.');
                    }
                }
            }
            $this->setFooter($tempFooter);
        }
        return $this->table($array);
    }
    
    
    #Reseting values. Useful, in case, someone decides to use same object several times
    public function default(): self
    {
        $this->setHeader([])->setFooter([])->setColGroup([])->setTypes([])->setSemantic(true)->setEditable(false)->setCheckbox(false)->setChecked(false)->setSanitize(true)->setCaption('')->setRepeatHeader(0)->setIdPrefix('simbiat')->setCounter('checkbox', 1)->setCounter('email', 1)->setCounter('url', 1)->setCounter('textarea', 1)->setCounter('file', 1)->setCounter('text', 1)->setCounter('tel', 1)->setCounter('password', 1)->setCounter('img', 1)->setCounter('price', 1)->setCounter('bytes', 1)->setCounter('seconds', 1)->setCounter('datetime', 1)->setCounter('time', 1)->setCounter('date', 1)->setCounter('html', 1)->setMultipleFiles(false)->setCurrencyCode('')->setCurrencyPrecision(2)->setCurrencyFraction(true);
        return $this;
    }
    
    private function table(array $array): string
    {
        #Get prefix
        $prefixid = $this->getIdPrefix();
        $table = '<'.($this->getSemantic() ? 'table' : 'div'.($this->getStyling() ? ' style="display:table;border-spacing:2px;"' : '')).' id="'.$prefixid.'table">';
        #Set caption
        if (!empty($this->getCaption())) {
            $table .= '<'.($this->getSemantic() ? 'caption' : 'div'.($this->getStyling() ? ' style="display:table-caption;text-align:center;"' : '')).' id="'.$prefixid.'caption">'.$this->getCaption().'</'.($this->getSemantic() ? 'caption' : 'div').'>';
        }
        #Set colgroups
        if ($this->groupscount != 0 && !empty($this->getColGroup()) && $this->getSemantic()) {
            $table .= '<'.($this->getSemantic() ? 'colgroup' : 'div'.($this->getStyling() ? ' style="display:table-column-group;"' : '')).' id="'.$prefixid.'colgroup">';
            foreach ($this->getColGroup() as $key=>$group) {
                $table .= '<'.($this->getSemantic() ? 'col' : 'div'.($this->getStyling() ? ' style="display:table-column;"' : '')).'  id="'.$prefixid.'col_'.$key.'" span="'.$group['span'].'"';
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
            $table .= '<'.($this->getSemantic() ? 'thead' : 'div'.($this->getStyling() ? ' style="display:table-header-group;font-weight:bold;text-align:center;"' : '')).' class="'.$prefixid.'thead"><'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).'>';
            #Add checkbox column (empty for header)
            if ($this->getCheckbox()) {
                   $table .=  '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;"' : '')).' class="'.$prefixid.'checkbox_dummy_field"></'.($this->getSemantic() ? 'th' : 'div').'>';
            }
            foreach ($this->getHeader() as $key=>$header) {
                $table .= '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;"' : '')).' class="'.$prefixid.'th_'.$key.'">'.$header.'</'.($this->getSemantic() ? 'th' : 'div').'>';
            }
            $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'></'.($this->getSemantic() ? 'thead' : 'div').'>';
        }
        $table .= '<'.($this->getSemantic() ? 'tbody' : 'div'.($this->getStyling() ? ' style="display:table-row-group;"' : '')).' id="'.$prefixid.'tbody">';
        #Check if array of array
        if ($this->multiflag) {
            foreach ($array as $row=>$subarray) {
                if ($row > 0 && $this->getRepeatHeader() !== 0 && !empty($this->getHeader()) && fmod($row+1, $this->getRepeatHeader()) === 0.0) {
                    $table .= '<'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).'>';
                    #Add checkbox column (empty for header)
                    if ($this->getCheckbox()) {
                           $table .=  '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;"' : '')).' class="'.$prefixid.'checkbox_dummy_field"></'.($this->getSemantic() ? 'th' : 'div').'>';
                    }
                    foreach ($this->getHeader() as $key=>$header) {
                        $table .= '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;"' : '')).' class="'.$prefixid.'th_'.$key.'">'.$header.'</'.($this->getSemantic() ? 'th' : 'div').'>';
                    }
                    $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'>';
                }
                $table .= '<'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).' id="'.$prefixid.'tr_'.$row.'">';
                #Add checkbox column
                if ($this->getCheckbox()) {
                    $table .=  '<'.($this->getSemantic() ? 'td' : 'div'.($this->getStyling() ? ' style="display:table-cell;"' : '')).' class="'.$prefixid.'checkbox_field" id="'.$prefixid.'td_checkbox_'.$row.'"><input type="checkbox" id="'.$prefixid.'checkbox_'.$row.'"'.($this->getChecked() ? ' checked' : '').'></'.($this->getSemantic() ? 'td' : 'div').'>';
                }
                $subarray = array_values($subarray);
                foreach ($subarray as $column=>$cell) {
                    $table .= '<'.($this->getSemantic() ? 'td' : 'div'.($this->getStyling() ? ' style="display:table-cell;"' : '')).' id="'.$prefixid.'td_'.$row.'_'.$column.'">'.$this->prepare($cell, $column, $row).'</'.($this->getSemantic() ? 'td' : 'div').'>';
                }
                $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'>';
            }
        } else {
            $table .= '<'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).' id="'.$prefixid.'tr_0">';
            $array = array_values($array);
            foreach ($array as $column=>$cell) {
                $table .= '<'.($this->getSemantic() ? 'td' : 'div'.($this->getStyling() ? ' style="display:table-cell;"' : '')).' id="'.$prefixid.'td_0_'.$column.'">'.$this->prepare($cell, $column).'</'.($this->getSemantic() ? 'td' : 'div').'>';
            }
            $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'>';
        }
        $table .= '</'.($this->getSemantic() ? 'tbody' : 'div').'>';
        #Set footer
        if (!empty($this->getFooter())) {
            $table .= '<'.($this->getSemantic() ? 'tfoot' : 'div'.($this->getStyling() ? ' style="display:table-footer-group;font-weight:bold;text-align:center;"' : '')).' id="'.$prefixid.'tfoot"><'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).'>';
            #Add checkbox column (empty for footer)
            if ($this->getCheckbox()) {
                    $table .=  '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;"' : '')).' class="'.$prefixid.'checkbox_dummy_field"></'.($this->getSemantic() ? 'th' : 'div').'>';
            }
            foreach ($this->getFooter() as $key=>$footer) {
                $table .= '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;"' : '')).' class="'.$prefixid.'th_'.$key.'">'.$footer.'</'.($this->getSemantic() ? 'th' : 'div').'>';
            }
            $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'></'.($this->getSemantic() ? 'tfoot' : 'div').'>';
        } else {
            #If no footer, but have header and use of header as footer is enabled - use header
            if ($this->getFooterHeader() && !empty($this->getHeader())) {
                $table .= '<'.($this->getSemantic() ? 'tfoot' : 'div'.($this->getStyling() ? ' style="display:table-footer-group;font-weight:bold;text-align:center;"' : '')).' id="'.$prefixid.'tfoot"><'.($this->getSemantic() ? 'tr' : 'div'.($this->getStyling() ? ' style="display:table-row;"' : '')).'>';
                #Add checkbox column (empty for footer)
                if ($this->getCheckbox()) {
                    $table .=  '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;"' : '')).' class="'.$prefixid.'checkbox_dummy_field"></'.($this->getSemantic() ? 'th' : 'div').'>';
                }
                foreach ($this->getHeader() as $key=>$footer) {
                    $table .= '<'.($this->getSemantic() ? 'th' : 'div'.($this->getStyling() ? ' style="display:table-cell;"' : '')).' class="'.$prefixid.'th_'.$key.'">'.$footer.'</'.($this->getSemantic() ? 'th' : 'div').'>';
                }
                $table .= '</'.($this->getSemantic() ? 'tr' : 'div').'></'.($this->getSemantic() ? 'tfoot' : 'div').'>';
            }
        }
        $table .= '</'.($this->getSemantic() ? 'table' : 'div').'>';
        return $table;
    }
    
    
    private function prepare($string, int $colnum, int $rownum = 0, bool $footer = false): string
    {
        $string = strval($string);
        #Determine type
        $string_type = $this->getTypes()[$colnum];
        if (is_array($string_type)) {
            $string_type = $string_type[$rownum];
        }
        #Get prefix
        $prefixid = $this->getIdPrefix();
        #Set ID for the element
        if (in_array($string_type, ['html','date','time','datetime','seconds','bytes','price','checkbox','email','url','textarea','text','tel','password','img','file','color'])) {
            if ($footer) {
                ${$string_type.'id'} = $prefixid.'footer_'.$colnum;
            } else {
                ${$string_type.'id'} = $prefixid.$string_type.'_'.$this->getCounter($string_type);
            }
        }
        if ($this->getSanitize() && $string_type != 'html') {
            $string = strip_tags($string);
        }
        switch ($string_type) {
            case 'date':
                if ($this->getEditable() && $footer === false) {
                    if (self::$SandClock) {
                        $string = (new \SandClock\Api)->setFormat('Y-m-d')->format($string);
                    }
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixid.'_'.$string_type.'" type="date" step="1" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="'.$string.'">';
                } else {
                    if (self::$SandClock) {
                        $string = (new \SandClock\Api)->setFormat($this->getDateFormat())->format($string);
                    }
                }
                break;
            case 'time':
                if ($this->getEditable() && $footer === false) {
                    if (self::$SandClock) {
                        $string = (new \SandClock\Api)->setFormat('H:i:s')->format($string);
                    }
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixid.'_'.$string_type.'" type="time" step="1" pattern="[0-9]{2}:[0-9]{2}:[0-9]{2}" value="'.$string.'">';
                } else {
                    if (self::$SandClock) {
                        $string = (new \SandClock\Api)->setFormat($this->getTimeFormat())->format($string);
                    }
                }
                break;
            case 'datetime':
                if ($this->getEditable() && $footer === false) {
                    if (self::$SandClock) {
                        $string = (new \SandClock\Api)->setFormat('Y-m-d\TH:i:s')->format($string);
                    }
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixid.'_'.$string_type.'" type="datetime-local" step="1" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}" value="'.$string.'">';
                } else {
                    if (self::$SandClock) {
                        $string = (new \SandClock\Api)->setFormat($this->getDateTimeFormat())->format($string);
                    }
                }
                break;
            case 'seconds':
            case 'bytes':
                if ($this->getEditable() && $footer === false) {
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixid.'_'.$string_type.'" type="number" step="1" min="0" inputmode="decimal" value="'.$string.'">';
                } else {
                    if ($string_type === 'bytes') {
                        if (self::$CuteBytes) {
                            $string = (new \CuteBytes\Api)->bytes($string);
                        }
                    } else {
                        if (self::$SandClock) {
                            $string = (new \SandClock\Api)->seconds($string, true, $this->getLanguage());
                        }
                    }
                }
                break;
            case 'price':
                #Expects integer string, where last 2 numbers are the "fractions" (like cents)
                if ($this->getCurrencyFraction()) {
                    $string = substr_replace(strval(intval($string)), '.', -$this->getCurrencyPrecision(), 0);
                    if (substr($string, 0, 1) === '.') {
                        $string = '0'.$string;
                    }
                } else {
                   $string = strval(number_format(floatval($string), $this->getCurrencyPrecision()));
                }
                if ((!$this->getEditable() || $footer === true) && !empty($this->getCurrencyCode())) {
                    #If code ends with space - palce it before the value
                    if (substr($this->getCurrencyCode(), -1) === ' ') {
                        $string = $this->getCurrencyCode().$string;
                    } else {
                        $string = $string.' '.$this->getCurrencyCode();
                    }
                }
                if ($this->getEditable() && $footer === false) {
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixid.'_'.$string_type.'" type="number" step="0.01" min="0.00" inputmode="decimal" value="'.$string.'">';
                }
                break;
            case 'checkbox':
                #Sanitizing values
                if (preg_match('/^on|yes$/mi', $string) === 1) {
                    $checkboxstatus = ' checked';
                } elseif (preg_match('/^off|no$/mi', $string) === 1) {
                    $checkboxstatus = '';
                } else {
                    if (boolval($string) === true) {
                        $checkboxstatus = ' checked';
                    } else {
                        $checkboxstatus = '';
                    }
                }
                $string = '<input type="checkbox" id="'.${$string_type.'id'}.'" class="'.$prefixid.'_checkbox"'.$checkboxstatus.($this->getEditable() && $footer === false ? '' : ' disabled').'>';
                break;
            case 'email':
            case 'url':
                #Processes string only if validates as actual URI/URL or e-mail
                if (preg_match(($string_type === 'url' ? self::$URIRegex : self::$eMailRegex), $string)) {
                    if ($string_type === 'url' && self::$PrettyURL) {
                        $string = (new \PrettyURL\PrettyURL)->pretty($string, $this->getSanitize());
                    }
                    if ($this->getEditable() && $footer === false) {
                        $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixid.'_'.$string_type.'" type="'.$string_type.'" inputmode="'.$string_type.'" value="'.$string.'">';
                    } else {
                        $string = '<a id="'.${$string_type.'id'}.'" class="'.$prefixid.'_'.$string_type.'" href="'.($string_type === 'url' ? '' : 'mailto:').$string.'"'.($string_type === 'url' ? ' target="_blank"' : '').'>'.$string.'</a>';
                    }
                } else {
                    $string = '<span id="'.${$string_type.'id'}.'" class="'.$prefixid.'_'.$string_type.'" type="'.$string_type.'">'.$string.'</span>';
                }
                break;
            case 'html':
                #If editable, treat as textarea
                if (!$this->getEditable() || $footer === true) {
                    $string = htmlentities($string, ENT_QUOTES | ENT_HTML5 | ENT_DISALLOWED);
                    break;
                }
            case 'textarea':
                if ($this->getEditable() && $footer === false) {
                    $string = '<textarea cols="'.$this->getTextareaSetting('cols').'" rows="'.$this->getTextareaSetting('rows').'" minlength="'.$this->getTextareaSetting('minlength').'" maxlength="'.$this->getTextareaSetting('maxlength').'" spellcheck="true" class="'.$prefixid.'_'.$string_type.'">'.$string.'</textarea>';
                }
                break;
            case 'text':
            case 'tel':
            case 'password':
                #Removing the entry, in case actual password is sent
                if ($string_type === 'password') {
                    $string = '';
                }
                if ($this->getEditable() && $footer === false) {
                    $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixid.'_'.$string_type.'" type="'.$string_type.'" inputmode="'.$string_type.'" value="'.$string.'"'.($string_type === 'password' ? 'pattern="'.self::$PasswordPattern.'"' : '').'>';
                }
                break;
            case 'img':
                #If editable, treat as input="file"
                if (!$this->getEditable() || $footer === true) {
                    #alt is set as "" to make some browsers consider images as non-essential. If an image needs to be considered as essential it's recommended not to show it through this library. Alternatively you can update it through JavaScript or other programmatic methods.
                    $string = '<img src="'.$string.'" alt="" decoding="async" class="'.$prefixid.'_'.$string_type.'">';
                    break;
                }
            case 'file':
                $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixid.'_'.$string_type.'" type="file"'.($this->getEditable() && $footer === false ? '' : ' disabled').($this->getMultipleFiles() ? ' multiple' : '').'>';
                break;
            case 'color':
                #Attempting to sanitize the value provided allowing only 0-9 and a-f characters and padding from left to 6 characters
                $string = '<input id="'.${$string_type.'id'}.'" class="'.$prefixid.'_color" type="color" value="#'.str_pad(substr(preg_replace(self::$ColorHexRegex, '', $string), 0, 6), 6, '0', STR_PAD_LEFT).'"'.($this->getEditable() && $footer === false ? '' : ' disabled').'>';
                break;
        }
        if ((!$this->getEditable() || $footer === true) && in_array($string_type, ['date','time','datetime','seconds','bytes','price','text','tel','password','textarea'])) {
            $string = '<span id="'.${$string_type.'id'}.'" class="'.$prefixid.'_'.$string_type.'">'.$string.'</span>';
        }
        return $string;
    }
    
    #####################
    #Setters and getters#
    #####################
    public function getHeader(): array
    {
        return $this->header;
    }
    
    public function setHeader(array $header): self
    {
        $this->header = array_values($header);
        return $this;
    }
    
    public function getFooter(): array
    {
        return $this->footer;
    }
    
    public function setFooter(array $footer): self
    {
        $this->footer = array_values($footer);
        return $this;
    }
    
    public function getRepeatHeader(): int
    {
        return $this->repeatheader;
    }
    
    public function setRepeatHeader(int $repeatheader): self
    {
        $this->repeatheader = $repeatheader;
        return $this;
    }
    
    public function getFooterHeader(): bool
    {
        return $this->footerHeader;
    }
    
    public function setFooterHeader(bool $footerHeader): self
    {
        $this->footerHeader = $footerHeader;
        return $this;
    }
    
    public function getCaption(): string
    {
        return $this->caption;
    }
    
    public function setCaption(string $caption): self
    {
        $this->caption = $caption;
        return $this;
    }
    
    public function getTypes(): array
    {
        return $this->types;
    }
    
    public function setTypes(array $types): self
    {
        $types = array_values($types);
        $this->types = $types;
        return $this;
    }
    
    public function getColGroup(): array
    {
        return $this->colgroup;
    }
    
    public function setColGroup(array $colgroup): self
    {
        $colgroup = array_values($colgroup);
        foreach ($colgroup as $key=>$group) {
            if (empty($group['span'])) {
                $colgroup[$key]['span'] = 1;
            } else {
                if (is_numeric($group['span'])) {
                    $colgroup[$key]['span'] = intval($group['span']);
                } else {
                    throw new \UnexpectedValueException('Types were sent, but have different length than array.');
                }
            }
            if (!empty($group['class'])) {
                if (is_array($group['class']) || is_object($group['class'])) {
                    throw new \UnexpectedValueException('Colgroup class provided is not castable to string.');
                } else {
                    $colgroup[$key]['class'] = strval($group['class']);
                }
            }
            if (!empty($group['style'])) {
                if (is_array($group['style']) || is_object($group['style'])) {
                    throw new \UnexpectedValueException('Colgroup style provided is not castable to string.');
                } else {
                    $colgroup[$key]['style'] = strval($group['style']);
                }
            }
            $this->groupscount = $this->groupscount + $colgroup[$key]['span'];
        }
        $this->colgroup = $colgroup;
        return $this;
    }
    
    public function getSemantic(): bool
    {
        return $this->semantic;
    }
    
    public function setSemantic(bool $semantic): self
    {
        $this->semantic = $semantic;
        return $this;
    }
    
    public function getStyling(): bool
    {
        return $this->styling;
    }
    
    public function setStyling(bool $styling): self
    {
        $this->styling = $styling;
        return $this;
    }
    
    public function getEditable(): bool
    {
        return $this->editable;
    }
    
    public function setEditable(bool $editable): self
    {
        $this->editable = $editable;
        return $this;
    }
    
    public function getCheckbox(): bool
    {
        return $this->checkbox;
    }
    
    public function setCheckbox(bool $checkbox): self
    {
        $this->checkbox = $checkbox;
        return $this;
    }
    
    public function getChecked(): bool
    {
        return $this->checked;
    }
    
    public function setChecked(bool $checked): self
    {
        $this->checked = $checked;
        return $this;
    }
    
    public function getSanitize(): bool
    {
        return $this->sanitize;
    }
    
    public function setSanitize(bool $sanitize): self
    {
        $this->sanitize = $sanitize;
        return $this;
    }
    
    public function getMultipleFiles(): bool
    {
        return $this->multiplefiles;
    }
    
    public function setMultipleFiles(bool $multiplefiles): self
    {
        $this->multiplefiles = $multiplefiles;
        return $this;
    }
    
    public function getIdPrefix(): string
    {
        return $this->idprefix;
    }
    
    public function setIdPrefix(string $idprefix): self
    {
        if (!empty($idprefix) && substr($idprefix, -1) !== '_') {
            $idprefix .= '_';
        }
        $this->idprefix = $idprefix;
        return $this;
    }
    
    public function getDateFormat(): string
    {
        return $this->dateformat;
    }
    
    public function setDateFormat(string $dateformat): self
    {
        $this->dateformat = $dateformat;
        return $this;
    }
    
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }
    
    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;
        return $this;
    }
    
    public function getCurrencyPrecision(): int
    {
        return $this->currencyPrecision;
    }
    
    public function setCurrencyPrecision(int $currencyPrecision): self
    {
        $this->currencyPrecision = $currencyPrecision;
        return $this;
    }
    
    public function getCurrencyFraction(): bool
    {
        return $this->currencyFraction;
    }
    
    public function setCurrencyFraction(bool $currencyFraction): self
    {
        $this->currencyFraction = $currencyFraction;
        return $this;
    }
    
    public function getTimeFormat(): string
    {
        return $this->timeformat;
    }
    
    public function setTimeFormat(string $timeformat): self
    {
        $this->timeformat = $timeformat;
        return $this;
    }
    
    public function getDateTimeFormat(): string
    {
        if (empty($this->datetimeformat)) {
            return $this->getDateFormat().' '.$this->getTimeFormat();
        } else {
            return $this->datetimeformat;
        }
    }
    
    public function setDateTimeFormat(string $datetimeformat): self
    {
        $this->datetimeformat = $datetimeformat;
        return $this;
    }
    
    public function getLanguage(): string
    {
        return $this->language;
    }
    
    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }
    
    private function getCounter(string $type): string
    {
        $curcount = $this->{'counter' . $type};
        $this->setCounter($type, $curcount+1);
        return strval($curcount);
    }
    
    private function setCounter(string $type, int $value): self
    {
        $this->{'counter' . $type} = $value;
        return $this;
    }
    
    public function getTextareaSetting(string $setting): string
    {
        return $this->textareaSettings[$setting];
    }
    
    public function setTextareaSetting(string $setting, string $value): self
    {
        if (!in_array($setting, ['rows', 'cols', 'minlength', 'maxlength'])) {
            throw new \UnexpectedValueException('Unsupported textarea setting provided. Only rows, cols, minlength, maxlength are supported.');
        } else {
            if ($value !== '' && !ctype_digit($value)) {
                throw new \UnexpectedValueException('Unsupported textarea setting value provided. Only integer string values and empty string are supported.');
            } else {
                $this->textareaSettings[$setting] = $value;
            }
        }
        return $this;
    }
    
    ##########
    #Checkers#
    ##########
    private function multi(array $array): bool
    {
        $length = count($array);
        #Check if multidimentional
        if (count(array_filter(array_values($array), 'is_array')) === $length) {
            #Check if all child arrays have same length
            if (count(array_unique(array_map('count', $array))) === 1) {
                $this->multiflag = true;
                return true;
            } else {
                throw new \UnexpectedValueException('Not all child arrays have same length.');
            }
        } else {
            #Check that all values are scalars
            if (count(array_filter(array_values($array), 'is_scalar')) === $length ) {
                $this->multiflag = false;
                return false;
            } else {
                throw new \UnexpectedValueException('Array contains both scalar and non-scalar values.');
            }
        }
    }
    
    private function assotiative(array $array): bool
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }
}
?>