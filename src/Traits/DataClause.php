<?php

namespace Datadriver\Traits;

use Exception;

trait DataClause
{
  /**
   * @param array|string $table
   * @param string $condition
   * @return \DataDriver\DataDriver
   */
  public function innerJoin($table,string $condition): \DataDriver\DataDriver
  {
    if(!is_array($table) && !is_string($table)) throw new Exception("The " + gettype($table) + " type is not accepted");
    if(is_array($table)) join(" ",$table);
    static::$query .= "INNER JOIN $table ON $condition";
    return $this;
  }

  /**
   * @param array|string $orderBy
   * @return \Datadriver\DataDriver 
   */
  public function orderBy($orderBy): \Datadriver\DataDriver 
  {
    if(!is_array($orderBy) && !is_string($orderBy)) throw new Exception("The " + gettype($orderBy) + " type is not accepted");

    if(is_array($orderBy)) $orderBy = join(",",$orderBy);

    static::$query .= "ORDER BY {$orderBy}";
    return $this;
  }
}
