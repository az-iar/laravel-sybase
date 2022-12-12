<?php

namespace Uepg\LaravelSybase\Database\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar as IlluminateGrammar;

class Grammar extends IlluminateGrammar
{
    protected $operators = [
        '=', '<', '>', '<=', '>=', '!<', '!>', '<>', '!=',
        'like', 'not like', 'between', 'ilike',
        '&', '&=', '|', '|=', '^', '^=',
    ];

    protected $builder;

    public function getBuilder()
    {
        return $this->builder;
    }

    public function compileSelect(Builder $query)
    {
        $this->builder = $query;

        $components = $this->compileComponents($query);

        return $this->concatenate($components);
    }

    protected function compileColumns(Builder $query, $columns)
    {
        if (! is_null($query->aggregate)) {
            return;
        }

        $select = $query->distinct ? 'select distinct ' : 'select ';

        // If there is a limit on the query, but not an offset, we will add the
        // top clause to the query, which serves as a "limit" type clause
        // within the SQL Server system similar to the limit keywords available
        // in MySQL.
        if ($query->limit > 0 && $query->offset <= 0) {
            $select .= 'top '.$query->limit.' ';
        }

        return $select.$this->columnize($columns);
    }

    protected function compileFrom(Builder $query, $table)
    {
        $from = parent::compileFrom($query, $table);

        if (is_string($query->lock)) {
            return $from.' '.$query->lock;
        }

        if (! is_null($query->lock)) {
            return $from.' with(rowlock,'.
                ($query->lock ? 'updlock,' : '').'holdlock)';
        }

        return $from;
    }

    protected function compileLimit(Builder $query, $limit)
    {
        return '';
    }

    protected function compileOffset(Builder $query, $offset)
    {
        return '';
    }

    public function compileTruncate(Builder $query)
    {
        return [
            'truncate table '.$this->wrapTable($query->from) => [],
        ];
    }

    public function getDateFormat()
    {
        return 'Y-m-d H:i:s.000';
    }

    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }

        return '['.str_replace(']', ']]', $value).']';
    }
}
