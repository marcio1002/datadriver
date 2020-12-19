<?php
namespace Datadriver\Helpers\Schema;

use Illuminate\Support\Collection;

class Mysql {

  use Sql;

  public function __construct(Collection $collection, string $clause, callable $callable)
  {
    static::$callable = $callable;
    static::$clause = $clause;
    $this->initCollect($collection);
  }
}