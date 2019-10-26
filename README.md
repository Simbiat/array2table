# Array To Table converter
This library is used for creating HTML tables using data provided to it in an array. When will you want to use it? I believe, there are 2 use-cases:

- You are presenting a lot of homogeneous data from your database
- You are creating some relatively simple form (like user settings) and do not want that much customization for it

Yes, this library can be used for creating forms, that you will be able to process further, once they are submitted. And the most unique feature here is that form elements will not be just text-fields: library supports checkboxes, date/time fields, colors, files, .etc. Each element will be created with the minimum required attributes, with automatically generated IDs and classes (for CSS customization or JavaScript interactivity, if required).

- [Data Types](#data-types)
- [Settings](#settings)

# How to use
The best way to understand what you will be getting is a live example, so check out `sample.html` somewhere near this README. There you will find 3 tables, that were generated using following code:
```php
echo (new \array2table\Api)->setIdPrefix('sem_edit')->setCaption('Semantic, editable')->setFooter(['#func_sum','',''])->setCurrencyCode('USD ')->setCurrencyPrecision(2)->setCurrencyFraction(true)->setCheckbox(true)->setChecked(true)->setEditable(true)->setTextareaSetting('cols', '20')->setTextareaSetting('rows', '5')->setTypes(['number','string',['text','password','tel','date', 'time', 'datetime', 'seconds', 'bytes', 'price', 'checkbox', 'email', 'url', 'html', 'img', 'color']])->generate(
    [
            ['Points'=>'1','Field'=>'Login','Value'=>'Simbiat'],
            ['1','Password','Simbiat'],
            ['1','Telephone','+74991752327'],
            ['1','Date of birth','12-05-1989'],
            ['1','Current time',time(),],
            ['1','Last login',time(),],
            ['1','Seconds','123456',],
            ['1','Image size','1234567',],
            ['1','Salary','123456',],
            ['1','Are you a robot?','No',],
            ['1','Email','simbiat@outlook.com',],
            ['1','Website','https://simbiat.ru',],
            ['1','Signature','<a href="https://simbiat.ru">Awesome website</a>'],
            ['1','Avatar','https://media.kitsu.io/users/avatars/41172/large.jpg'],
            ['1','Favorite color','006E72'],
    ]
);

echo (new \array2table\Api)->setIdPrefix('sem_nonedit')->setCaption('Semantic, non-editable')->setFooter(['#func_sum','',''])->setCurrencyCode('USD ')->setCurrencyPrecision(2)->setCurrencyFraction(true)->setCheckbox(true)->setChecked(true)->setEditable(false)->setTextareaSetting('cols', '20')->setTextareaSetting('rows', '5')->setTypes(['number','string',['text','password','tel','date', 'time', 'datetime', 'seconds', 'bytes', 'price', 'checkbox', 'email', 'url', 'html', 'img', 'color']])->generate(
    [
            ['Points'=>'1','Field'=>'Login','Value'=>'Simbiat'],
            ['1','Password','Simbiat'],
            ['1','Telephone','+74991752327'],
            ['1','Date of birth','12-05-1989'],
            ['1','Current time',time(),],
            ['1','Last login',time(),],
            ['1','Seconds','123456',],
            ['1','Image size','1234567',],
            ['1','Salary','123456',],
            ['1','Are you a robot?','No',],
            ['1','Email','simbiat@outlook.com',],
            ['1','Website','https://simbiat.ru',],
            ['1','Signature','<a href="https://simbiat.ru">Awesome website</a>'],
            ['1','Avatar','https://media.kitsu.io/users/avatars/41172/large.jpg'],
            ['1','Favorite color','006E72'],
    ]
);

echo (new \array2table\Api)->setIdPrefix('nonsem_nonedit')->setCaption('Non-semantic, non-editable')->setSemantic(false)->setStyling(true)->setFooter(['#func_sum','',''])->setCurrencyCode('USD ')->setCurrencyPrecision(2)->setCurrencyFraction(true)->setCheckbox(true)->setChecked(true)->setEditable(false)->setTextareaSetting('cols', '20')->setTextareaSetting('rows', '5')->setTypes(['number','string',['text','password','tel','date', 'time', 'datetime', 'seconds', 'bytes', 'price', 'checkbox', 'email', 'url', 'html', 'img', 'color']])->generate(
    [
            ['Points'=>'1','Field'=>'Login','Value'=>'Simbiat'],
            ['1','Password','Simbiat'],
            ['1','Telephone','+74991752327'],
            ['1','Date of birth','12-05-1989'],
            ['1','Current time',time(),],
            ['1','Last login',time(),],
            ['1','Seconds','123456',],
            ['1','Image size','1234567',],
            ['1','Salary','123456',],
            ['1','Are you a robot?','No',],
            ['1','Email','simbiat@outlook.com',],
            ['1','Website','https://simbiat.ru',],
            ['1','Signature','<a href="https://simbiat.ru">Awesome website</a>'],
            ['1','Avatar','https://media.kitsu.io/users/avatars/41172/large.jpg'],
            ['1','Favorite color','006E72'],
    ]
);
```

Here's what the code does:
1. Create new object with `(new \array2table\Api)`. Obviously load the library before that.
2. Optionally set a `prefix` for elements' IDs and Classes with `setIdPrefix('prefix')`. Very useful, in case you will have multiple tables like that on one page, like `sample.html` does.
3. Optionally set `caption` for the table with `setCaption('caption')`. If this is set a `<caption></caption>` will be added to the table if it's semantic or respective `<div></div>` if it's not. Basically, this is a name of the table.
4. In case of non-semantic tables we have `setSemantic(false)`. This forces use of `<div></div>` elements instead if regular (semantic) table elements (table, td, tr, th, .etc). When creating actual tables, it is recommended to use semantic approach (thus default is `true`), but in some cases you may want to have `<div></div>`.
5. Optionally enable basic styling for `<div></div>` elements for non-semantic approach with `setStyling(true)`. This will add inline `style` attributes to make non-semantic table look more like its semantic counterpart. Styling is based on WebKit stylesheet at the time of writing.
6. Optionally set `footer` with `setFooter(['','',''])`. If this is set `<footer></footer>` will be added to the table if it's semantic or respective `<div></div>` if it's not. This is a row that will appear at the bottom of the table. If set, its length will be checked against the number of columns the data have and if different - throw an exception. `footer` can be used to repeate `header` with `setFooterHeader(true)`. In case of `sample.html` we also use `#func_sum` to calculate total (sum) of the values in respective column.
7. Optionally set currency for `currency` values with `setCurrencyCode('USD ')`. Adding space at the and of the value will place the currency before the values. Default is `''`, that empty string.
8. Optionally set `precision` for `currency` values with `setCurrencyPrecision(2)`. This determines number of digits after dot. Default is `2`.
9. Optionally set initial format used by `currency` values with `setCurrencyFraction(true)`. If `true` converter will expect values to be "fractions", like cents for USD, thus will consider the last 2 digits of a value as cents and put them after the dot. If `false` - will expect a float and/or convert to it.
10. Optionally add checkbox for each lie with `setCheckbox(true)`. This may be useful when you want to allow user to remove some entries, for example. Set is as checked with `setChecked(true)`.
11. Optionally make the values of the table editable with `setEditable(true)`. This will replace values with appropriate `<input>` elements.
12. Optionally adjust size of `<textarea>` elements with `setTextareaSetting('cols', '20')->setTextareaSetting('rows', '5')`.
13. Set values types with `setTypes(['number','string',['text','password','tel','date', 'time', 'datetime', 'seconds', 'bytes', 'price', 'checkbox', 'email', 'url', 'html', 'img', 'color']])`. In the example 2 first columns (array elements) have one type for all values in them. Both of them are not, actually, a supported type from the list, thus they will turn into regular `<span></span>` elements. The 3rd column uses an array of types with separate type for each row. It's not required to have different types for each: this is done only for the sample purpose to showcase all of currently supported types. It is required to have the same number of types as you have actual data rows or an exception will be thrown.
14. Actually generate the table with
`generate(
    [
            ['Points'=>'1','Field'=>'Login','Value'=>'Simbiat'],
            ['1','Password','Simbiat'],
            ['1','Telephone','+74991752327'],
            ['1','Date of birth','12-05-1989'],
            ['1','Current time',time(),],
            ['1','Last login',time(),],
            ['1','Seconds','123456',],
            ['1','Image size','1234567',],
            ['1','Salary','123456',],
            ['1','Are you a robot?','No',],
            ['1','Email','simbiat@outlook.com',],
            ['1','Website','https://simbiat.ru',],
            ['1','Signature','<a href="https://simbiat.ru">Awesome website</a>'],
            ['1','Avatar','https://media.kitsu.io/users/avatars/41172/large.jpg'],
            ['1','Favorite color','006E72'],
    ]`
Each "inner" array in "outer" array represents a row, while each element in "inner" arrays - respective column. In sample 1st row is using an associative array for optional header value (creates `<header></header>`). Alternatively `setHeader([])` can be used to the same effect.

# Data types
<table>
	<tr>
		<th>Type</th>
		<th>Input element</th>
		<th>Description<th>
	</tr>
	<tr>
		<td>date</td>
		<td><code>&lt;input id="element_count" class="element_class" type="date" step="1" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value=""&gt;</code></td>
		<td>If <code>Simbiat\SandClock</code> library is registered will attempt to show date according to its logic, otherwise will keep the value as is. If editable, will enforce certain format for the value, when sending the form.<td>
	</tr>
	<tr>
		<td>time</td>
		<td><code>&lt;input id="element_count" class="element_class" type="time" step="1" pattern="[0-9]{2}:[0-9]{2}:[0-9]{2}" value=""&gt;</code></td>
		<td>If <code>Simbiat\SandClock</code> library is registered will attempt to show time according to its logic, otherwise will keep the value as is. If editable, will enforce certain format for the value, when sending the form.<td>
	</tr>
	<tr>
		<td>datetime</td>
		<td><code>&lt;input id="element_count" class="element_class" type="datetime-local" step="1" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}" value=""&gt;</code></td>
		<td>If <code>Simbiat\SandClock</code> library is registered will attempt to show date and time according to its logic, otherwise will keep the value as is. If editable, will enforce certain format for the value, when sending the form.<td>
	</tr>
	<tr>
		<td>seconds</td>
		<td><code>&lt;input id="element_count" class="element_class" type="number" step="1" min="0" inputmode="decimal" value="value"&gt;</code></td>
		<td>If <code>Simbiat\SandClock</code> library is registered will attempt to convert seconds to time length according to its logic, otherwise will keep the value as is. If editable, will attempt to force decimal virtual keyboard, depending on browser support<td>
	</tr>
	<tr>
		<td>bytes</td>
		<td><code>&lt;input id="element_count" class="element_class" type="number" step="1" min="0" inputmode="decimal" value="value"&gt;</code></td>
		<td>If <code>Simbiat\CuteBytes</code> library is registered will attempt to convert bytes to other sizes according to its logic, otherwise will keep the value as is. If editable, will attempt to force decimal virtual keyboard, depending on browser support<td>
	</tr>
	<tr>
		<td>price</td>
		<td><code>&lt;input id="element_count" class="element_class" type="number" step="0.01" min="0.00" inputmode="decimal" value="value"&gt;</code></td>
		<td>Converts provided value into a float with an optional currency sign representing an amount of that currency. If editable, will attempt to force decimal virtual keyboard, depending on browser support<td>
	</tr>
	<tr>
		<td>checkbox</td>
		<td><code>&lt;input id="element_count" class="element_class" type="checkbox"&gt;</code></td>
		<td>Converts value to a checkbox. Values like <code>yes</code>, <code>no</code>, <code>on</code>, <code>off</code> will be converted to <code>true</code> or <code>false</code>, while for others <code>boolval</code> will be used for the same. If result is <code>true</code> - checkbox will be ticked (<code>checked</code> attribute added). If not editable, <code>disabled</code> attribute will be added.<td>
	</tr>
	<tr>
		<td>email</td>
		<td><code>&lt;input id="element_count" class="element_class" type="email" inputmode="email" value="value"&gt;</code></td>
		<td>Returns <code><a></a></code> element using <code>mailto:</code> URI if validates as actual email. If not - will be treated as regular text in <code><span></span></code>. If editable and browser implements input type <code>email</code> - will validate the value when sending form.<td>
	</tr>
	<tr>
		<td>url</td>
		<td><code>&lt;input id="element_count" class="element_class" type="url" inputmode="url" value="value"&gt;</code></td>
		<td>Returns <code><a></a></code> element if validates as actual email. If not - will be treated as regular text in <code><span></span></code>. If editable and browser implements input type <code>url</code> - will validate the value when sending form. If <code>Simbiat\PrettyURL</code> is registered, will sanitize the value before processing.<td>
	</tr>
	<tr>
		<td>html</td>
		<td><code>&lt;textarea id="element_count" class="element_class" cols="cols" rows="rows" minlength="minlength" maxlength="maxlength" spellcheck="true"&gt;value&lt;/textarea&gt;</code></td>
		<td>Treats string same as <code>textarea</code>, but sanitizes the value beofre processing.<td>
	</tr>
	<tr>
		<td>textarea</td>
		<td><code>&lt;textarea id="element_count" class="element_class" cols="cols" rows="rows" minlength="minlength" maxlength="maxlength" spellcheck="true"&gt;value&lt;/textarea&gt;</code></td>
		<td>Shows text as <code><span></span></code>. If editable, shows appropriate textarea. **Be extremely careful with the text you allow users to input here, because it can be easily exploited! Use some kind of sanitization process when submitting form!**<td>
	</tr>
	<tr>
		<td>text</td>
		<td><code>&lt;input id="element_count" class="element_class" type="text" inputmode="text" value="value"&gt;</code></td>
		<td>Returns regular text or respective <code><input></code> element.<td>
	</tr>
	<tr>
		<td>tel</td>
		<td><code>&lt;input id="element_count" class="element_class" type="tel" inputmode="tel" value="value"&gt;</code></td>
		<td>Same as <code>text</code>, but will enforce specific virtual keyboard if browser supports it.<td>
	</tr>
	<tr>
		<td>password</td>
		<td><code>&lt;input id="element_count" class="element_class" type="password" inputmode="password" value="value" pattern="password_pattern"&gt;</code></td>
		<td>Same as <code>text</code>, but will clear the value (always) and apply pattern. Pattern is stored as <code>public static $PasswordPattern</code> and can be changed if you want to, but it's advised against in order to comply with the minimum complexity recommendations.<td>
	</tr>
	<tr>
		<td>img</td>
		<td><code>&lt;input id="element_count" class="element_class" type="file"&gt;</code></td>
		<td>If not editable, returns <code><img></code> element with source equal to value provided. Otherwise acts same as <code>file</code>.<td>
	</tr>
	<tr>
		<td>file</td>
		<td><code>&lt;input id="element_count" class="element_class" type="file"&gt;</code></td>
		<td>Provides field for file upload, if editable (if not, the element will be disabled). Use <code>setMultipleFiles(true)</code> to allow upload of multiple files.<td>
	</tr>
	<tr>
		<td>color</td>
		<td><code>&lt;input id="'.${$string_type.'id'}.'" class="'.$prefixid.'_color" type="color" value="#ffffff" pattern="^#?([a-fA-F0-9]{6})$"&gt;</code></td>
		<td>Returns color picker if supported by browser. No need to send <code>#</code> when providing data, since it will be added automatically.<td>
	</tr>
</table>

# Settings
<table>
	<tr>
		<th>Setter function</th>
		<th>Default value</th>
		<th>Description</th>
	</tr>
	<tr>
		<td><code>setSemantic(bool $semantic)</code></td>
		<td><code>true</code></td>
		<td>Generate <code>&lt;table&gt;</code> (semantic) if true or <code>&lt;div&gt;</code> (non-semantic) if false.</td>
	</tr>
	<tr>
		<td><code>setStyling(bool $styling)</code></td>
		<td><code>false</code></td>
		<td>Force basic inline styling for <code>&lt;div&gt;</code> variant. Custom styling is recommended.</td>
	</tr>
	<tr>
		<td><code>setCheckbox(bool $checkbox)</code></td>
		<td><code>false</code></td>
		<td>Generate with first column being a checkbox with unique ID if true. Works for multi-arrays only.</td>
	</tr>
	<tr>
		<td><code>setChecked(bool $checked)</code></td>
		<td><code>false</code></td>
		<td>Generate with first column checkbox checked if true.</td>
	</tr>
	<tr>
		<td><code>setSanitize(bool $sanitize)</code></td>
		<td><code>true</code></td>
		<td>Attempt to strip tags and encode HTML entities, unless <code>html</code> data type.</td>
	</tr>
	<tr>
		<td><code>setEditable(bool $editable)</code></td>
		<td><code>false</code></td>
		<td>Flag to allow edit of the editable fields.</td>
	</tr>
	<tr>
		<td><code>setCaption(string $caption)</code></td>
		<td><code>''</code></td>
		<td>Optional caption (name) for tables.</td>
	</tr>
	<tr>
		<td><code>setHeader(array $header)</code></td>
		<td><code>[]</code></td>
		<td>Optional header for tables. Expects array with same length as number of columns in data provided. Alternatively will attempt to use keys from first row, if it's an associative array.</td>
	</tr>
	<tr>
		<td><code>setRepeatHeader(int $repeatheader)</code></td>
		<td><code>0</code></td>
		<td>If value is not 0, will repeat header every X number of lines, where X is the value set by this setter. Recommended for large tables.</td>
	</tr>
	<tr>
		<td><code>setFooter(array $footer)</code></td>
		<td><code>[]</code></td>
		<td>Optional footer for tables. Expects array with same length as number of columns in data provided. Supports functions for columns with singular data type: <code>'#func_sum'</code> (sum of all values in column), <code>'#func_avg'</code> (average of all values in column), <code>'#func_min'</code> (lowest value in column), <code>'#func_max'</code> (maximum value in column)</td>
	</tr>
	<tr>
		<td><code>setFooterHeader(bool $footerHeader)</code></td>
		<td><code>false</code></td>
		<td>Use header text in footer.</td>
	</tr>
	<tr>
		<td><code>setColGroup(array $colgroup)</code></td>
		<td><code>[]</code></td>
		<td>Setter for optional <code>colgroup</code> definition, which allows grouping of columns through HTML classes. Expect array like <code>[['span'=>2,'class'=>'col_class','style'=>'col_style'],[...],...,[...]]</code>. <code>'span'</code> expect a numeric value identifying number of columns in a group. <code>'class'</code> expects a class that will be additionally applied to the group. <code>'style'</code> expects a CSS style string that will be additionally applied to group.</td>
	</tr>
	<tr>
		<td><code>setTypes(array $types)</code></td>
		<td><code>[]</code></td>
		<td>Optional list of types to be applied to columns and rows. Expects an array in format as described in [How to use](#how-to-use).</td>
	</tr>
	<tr>
		<td><code>setIdPrefix(string $idprefix)</code></td>
		<td><code>'simbiat'</code></td>
		<td>Optional prefix for elements' IDs and some of the classes.</td>
	</tr>
	<tr>
		<td><code>setMultipleFiles(bool $multiplefiles)</code></td>
		<td><code>false</code></td>
		<td>Option to allow multiple files upload for file fields.</td>
	</tr>
	<tr>
		<td><code>setDateFormat(string $dateformat)</code></td>
		<td><code>'Y-m-d'</code></td>
		<td>Date format to use with <code>Simbiat\SandClock</code> library for <code>date</code> type.</td>
	</tr>
	<tr>
		<td><code>setTimeFormat(string $timeformat)</code></td>
		<td><code>'H:i:s'</code></td>
		<td>Time format to use with <code>Simbiat\SandClock</code> library for <code>time</code> type.</td>
	</tr>
	<tr>
		<td><code>setDateTimeFormat(string $datetimeformat)</code></td>
		<td><code>''</code></td>
		<td>Date and time format to use with <code>Simbiat\SandClock</code> library for <code>date</code> type. If empty will combine date format and time format set by respective setters as <code>date time</code>.</td>
	</tr>
	<tr>
		<td><code>setLanguage(string $language)</code></td>
		<td><code>'en'</code></td>
		<td>Language to use with <code>Simbiat\SandClock</code> library for <code>seconds</code> type.</td>
	</tr>
	<tr>
		<td><code>setTextareaSetting(string $setting, string $value)</code></td>
		<td><code>[
        'rows'=>'20',
        'cols'=>'2',
        'minlength'=>'',
        'maxlength'=>'',
    ]</code></td>
		<td>Customization options for <code>textarea</code> elements.</td>
	</tr>
	<tr>
		<td><code>setCurrencyCode(string $currencyCode)</code></td>
		<td><code>''</code></td>
		<td>Set an optional currency code (or symbol) for <code>price</code> type. Add space at the end to put it before the value.</td>
	</tr>
	<tr>
		<td><code>setCurrencyPrecision(int $currencyPrecision)</code></td>
		<td><code>2</code></td>
		<td>Set precision of floating point (number of digits after the dot) for <code>price</code> type.</td>
	</tr>
	<tr>
		<td><code>setCurrencyFraction(bool $currencyFraction)</code></td>
		<td><code>true</code></td>
		<td>Set to <code>false</code> to treat values for <code>price</code> type as floats. Treat values as fractions (like cents in case of USD) by default.</td>
	</tr>
</table>