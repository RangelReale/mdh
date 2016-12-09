# PHP Multi Data Handler

PHP Multi Data Handler (MDH) is a library used to format and parse data 
**to and from PHP format**.
Using multiple converters, is is possible to convert between formats.

The library is very flexible, allowing datatype registration, fallback to
a default conversion, and overriding any conversion.

Date and time values are **always** returned as a DateTime class. The library
accepts timestamps as input, but the output is always a DateTime class.

### Usage

```php
// creates the base class (the converters "default" and "user" are default)
$mdh = new \RangelReale\mdh\MDH();
// use the en-US locale
$mdh->locale = 'en-US';
// add a mysql converter
$mdh->setConverter('db', new \RangelReale\mdh\mysql\MySQLConverter($mdh));

// using the "db" converter, format a datetime from PHP format to MySQL format
echo $mdh->db->format('datetime', time()), '<br/>'; // 2016-12-09 13:26:42
// using the "db" converter, parse a datetime from MySQL format to PHP format
echo print_r($mdh->db->parse('datetime', '2016-01-10 15:00:00'), true), '<br/>'; // DateTime Object ( [date] => 2016-01-10 15:00:00.000000 [timezone_type] => 3 [timezone] => America/Sao_Paulo ) 

// using the "user" converter, format a datetime from PHP format to user format (using locale)
echo $mdh->user->format('datetime', time()), '<br/>'; // 12/9/16, 1:34 PM

// convert a datetime value from "db" to "user" format
echo $mdh->convert('db', 'user', 'datetime', '2016-10-10 21:00:00'), '<br/>'; // 10/10/16, 9:00 PM
```

### Multiple conversion

Multiple fields can be converted at the same time.

```php
$multi = new \RangelReale\mdh\MultiConversion([
    'value' => 'decimal',
    'date_created' => 'datetime',
]);
echo print_r($mdh->convertMulti(null, 'user', $multi, [
    'value' => 15.1872,
    'date_created' => time(),
])->result, true), '<br/>'; // Array ( [value] => 15.19 [date_created] => 12/9/16, 1:39 PM ) 
```

### Default data types

 * raw
 * text
 * boolean
 * integer
 * date
 * time
 * datetime
 * decimal
 * currency
 * decimalfull
 * bytes
 * timeperiod
 * bitmask

### Author

Rangel Reale
