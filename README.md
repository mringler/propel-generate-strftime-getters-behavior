Generate strftime()-Getters Behavior for [Propel2](https://github.com/propelorm/Propel2)
==================================

The getters for date columns generated by Propel can be used to get formatted date strings. However, as the default implementation uses `DateTime::format()`, no matter what locale is set, day and month names are always given in English.

With this behavior, additional getters are generated that use `strftime()` and thus use local day and month names.

Example
-------

```php
setlocale(LC_ALL, 'de_DE');   // set locale different from english

$dateFormat = 'l s. F Y';
echo $modelObject->getMyDateColumn($dateFormat); // still outputs english day and month names, i.e. "Monday, 9. January 2021"

$strftimeFormat = '%A, %e. %B %Y';
echo $modelObject->getMyDateColumnUsingLocale($strftimeFormat); // outputs localized day and month, i.e. "Montag, 9. Januar 2021"
```

Installation
------------

From command line:

```bash
composer config repositories.repo-name vcs https://github.com/mringler/propel-generate-strftime-getters-behavior
composer require mringler/propel-generate-strftime-getters-behavior:dev-master
```

Or adding manually to composer.json before running `composer update`:

```json
{
  "require": {
    "mringler/propel-generate_strftime_getters-behavior": "dev-master"
  },
  "repositories": {
    "repo-name": {
      "type": "vcs",
      "url": "https://github.com/mringler/propel-generate-strftime-getters-behavior.git"
    }
  }
}
```

Usage
-----

```xml
<table name="MyTable">
  <column name="my_timestamp" phpName="MyTimestamp" type="TIMESTAMP" />
  <column name="my_datetime" phpName="MyDatetime" type="DATETIME" />
  <column name="my_date" phpName="MyDate" type="DATE" />
  ...
  <behavior name="generate_strftime_getters"/>
</table>
```

will generate additional methods for every column in the table with a time-based type: 

```php
MyTable::getMyTimestampUsingLocale($format=null);
MyTable::getMyDatetimeUsingLocale($format=null);
MyTable::getMyDateUsingLocale($format=null);
```

Recognized types are DATE, DATETIME and TIMESTAMP.

Adjust method name
---------

The generated method name can be adjusted by supplying a format string through the "function_name_format" parameter.

The format string must contain exaclty one "%s", which will be replaced by the name of the column.

For example, to generate methods like `MyTable::getMyColumnUsingStrftime($format=null)`, set it to "`get%sUsingStrftime`":

```xml
<behavior name="generate_strftime_getters">
  <parameter name="function_name_format" value="get%sUsingStrftime" />
</behavior>
```

Specify columns
---------

To generate the localized getters for only some columns, use the `columns` parameter:

```xml
<table name="MyTable">
  ...
  <behavior name="generate_strftime_getters">
    <parameter name="columns" value="my_datetime, my_date" />
  </behavior>
</table>
```


License
-------

See the [LICENSE](LICENSE) file.
