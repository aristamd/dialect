<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;

class MockJsonDialectModel extends Model
{
    use \Eloquent\Dialect\Json;

    protected $jsonColumns;

    public function setJsonColumns(array $columns)
    {
        $this->jsonColumns = $columns;
    }

    public function getCustomGetAttribute()
    {
        return "custom getter result";
    }

    public function setCustomSetAttribute( $value )
    {
        $this->setJsonAttribute($this->jsonAttributes['custom_set'], 'custom_set', "custom {$value}");
    }
}
