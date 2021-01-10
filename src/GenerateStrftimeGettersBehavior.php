<?php

/**
 * This file is part of the propel-localized-date-behavior package.
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace MRingler\Propel\Behavior\GenerateStrftimeGetters;

use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Column;

class GenerateStrftimeGettersBehavior extends Behavior
{
    protected $parameters = array(
        'function_name_format' => 'get%sUsingLocale',
        'columns'              => null
    );
    
    const SQL_DATE_TYPES = ['DATE', 'DATETIME', 'TIMESTAMP'];

    public function objectMethods(ObjectBuilder $builder)
    {
        $dateColumns    = $this->getColumns();
        $codeGenerator  = [$this, 'getLocalizedGetterCodeForColumn'];
        $codeSplits     = array_map($codeGenerator, $dateColumns);
        return implode("\n", $codeSplits);
    }
    
    private function getColumns(): array
    {
        $columnParam = $this->parameters['columns'];
        if( empty($columnParam))
        {
            return $this->getAllDateColumnsInTable();
        }
        $rawColumns     = explode(',', $columnParam);
        $columnNames    = array_map('trim', $rawColumns);
        $columnGetter   = [$this, 'getDateColumnByName'];
        return array_map($columnGetter, $columnNames);
    }
    
    private function getDateColumnByName(string $columnName): Column
    {
        $table = $this->getTable();
        if( ! $table->hasColumn($columnName))
        {
            $msgFormat  = 'Error when resolving behavior "localized_date": column "%s" is not available in table "%s"';
            $msg        = sprintf($msgFormat, $columnName, $table->getName());
            throw new \InvalidArgumentException($msg);
        }
        $column = $table->getColumn($columnName);
        if( ! $this->isDateColumn($column))
        {
            $msgFormat  = 'Error when resolving behavior "localized_date": column "%s" in table "%s" is not a date column, but "%s"';
            $msg        = sprintf($msgFormat, $columnName, $table->getName(), $column->getType());
            throw new \InvalidArgumentException($msg);
        }
        return $column;
    }
        
    private function isDateColumn(Column $column): bool
    {
        $type = $column->getType();
        return in_array($type, self::SQL_DATE_TYPES);
    }
    
    private function getAllDateColumnsInTable(): array
    {
        $table              = $this->getTable();
        $allColumns         = $table->getColumns();
        $dateColumnFilter   = [$this, 'isDateColumn'];
        return array_filter($allColumns, $dateColumnFilter);
    }
    
    private function buildFunctionName(Column $column): string
    {
        $format     = $this->getValidatedFunctionNameFormat();
        $columnName = $column->getPhpName();
        return sprintf($format, $columnName);
    }
    
    private function getValidatedFunctionNameFormat(): string
    {
        $format             = $this->parameters['function_name_format'] ?? 'get%sUsingLocale';
        $noofReplacments    = substr_count($format, '%s');
        if( $noofReplacments !== 1)
        {
            $msg = sprintf(self::WRONG_FUNCTION_NAME_FORMAT_MESSAGE, $format, $noofReplacments);
            throw new \InvalidArgumentException($msg);
        }
        return $format;
    }
    
    private function getLocalizedGetterCodeForColumn(Column $column): string
    {
        $functionName   = $this->buildFunctionName($column);
        $dataProperty   = $column->getName();
        return sprintf(self::FUNCTION_TEMPLATE, $functionName, $dataProperty);
    }
    
    const FUNCTION_TEMPLATE = <<<'EOT'
    
/**
 * Get the [optionally formatted] temporal [date] column value using strftime, which supports localization.
 *
 * @param string|null $format The date/time format string in strftime()-style.
 *   If format is NULL, then the raw DateTime object will be returned.
 *
 * @return string|DateTime|null Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00
 *
 * @throws PropelException - if unable to parse/validate the date/time value.
 *
 * @link http://php.net/strftime
 * @see strftime()
 */
public function %s($format = null)
{
    $value = $this->%s;
    if ($format === null) {
        return $value;
    }
    if ( ! $value instanceof \DateTimeInterface)
    {
        return null;
    }
    return strftime($format, $value->getTimestamp());
}
EOT;
    
    const WRONG_FUNCTION_NAME_FORMAT_MESSAGE = <<< 'EOT'
Error when resolving behavior "localized_date": 
    Parameter "function_name_format" must contain exactly one occurence of "%%s"
    i.e. "get%%sWithLocalNames" to generate function names like "getMyDateColumnWithLocalNames()"
    
    Supplied value is "%s" (%d occurrences)
EOT;
}
