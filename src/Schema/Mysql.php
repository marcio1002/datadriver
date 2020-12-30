<?php
namespace Datadriver\Schema;

use Illuminate\Support\Collection;

class Mysql  extends Sql {

  public function __construct(Collection $collection, string $clause)
  {
    parent::__construct($collection, $clause);
  }

  public function __destruct()
  {
    $this->dispatchClause();
  }
}