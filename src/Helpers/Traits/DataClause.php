<?php

namespace Datadriver\Helpers\Traits;

use Datadriver\DataDriver;
use Exception;

trait DataClause
{

  /**
   * @param string $column
   * @param string $operator
   * @param mixed $values
   * @return \DataDriver\DataDriver
   */
  public function where(string $column, string $operator, $values): self
  {
    $this->setCollection([$values]);

    $val = (!empty(static::$subQuery)) ? "subQuery" : "query";

    if (stripos(static::$$val, "WHERE"))
      static::$$val .= " AND $column $operator ? ";
    else
      static::$$val .= " WHERE $column $operator ? ";

    return $this;
  }

  /**
   * @param string $column
   * @param mixed|\DataDriver\DataDriver ...$values
   * @return \DataDriver\DataDriver
   */
  public function whereIn(string $column, ...$values): self
  {
    if ($values[0]  instanceof DataDriver) {
      $values[0] = $this->__toString();
      static::$subQuery = "";
    }else {
      $this->setCollection($values);
      $values = array_fill(0,count($values),"?");
    }

    $val = (!empty(static::$subQuery)) ? "subQuery" : "query";

    $values = join(",", $values);

    if (stripos(static::$$val, "WHERE"))
      static::$$val .= " AND $column IN ($values) ";
    else
      static::$$val .= " WHERE $column IN ($values) ";
    return $this;
  }

  /**
   * @param string $column
   * @param  mixed|\DataDriver\DataDriver ...$values
   * @return \DataDriver\DataDriver
   */
  public function whereNotIn(string $column, ...$values): self
  {
    if ($values[0]  instanceof DataDriver) {
      $values[0] = $this->__toString();
      static::$subQuery = "";
    }else {
      $this->setCollection($values);
      $values = array_fill(0,count($values),"?");
    }

    $val = (!empty(static::$subQuery)) ? "subQuery" : "query";

    $values = join(",", $values);

    if (stripos(static::$$val, "WHERE"))
      static::$$val .= " AND $column  NOT IN ($values)";
    else
      static::$$val .= " WHERE $column NOT IN ($values)";
    return $this;

    return $this;
  }

  /**
   * @param array|\DataDriver\DataDriver $expression
   * @param array|\DataDriver\DataDriver $expression2
   * @return \DataDriver\DataDriver
   */
  public function whereOr($expression, $expression2): self
  {
    if ($expression instanceof DataDriver) {
      $expression = $this->toString();
      static::$subQuery = "";
    } else {
      $this->setCollection([array_pop($expression)]);
      $expression[2] = "?";
      $expression =  join(" ", $expression);
    }


    if ($expression2 instanceof DataDriver) {
      $expression2 = $this->toString();
      static::$subQuery = "";
    } else {
      $this->setCollection([array_pop($expression2)]);
      $expression2[2] = "?";
      $expression2 =  join(" ", $expression2);
    }


    $val = (!empty(static::$subQuery)) ? "subQuery" : "query";

    if (stripos(static::$$val, "WHERE"))
      static::$$val .= "AND $expression OR $expression2 ";
    else
      static::$$val .= "WHERE $expression OR $expression2";

    return $this;
  }

  /**
   * @param array|string $table
   * @param string|\DataDriver\DataDriver $condition
   * @return \DataDriver\DataDriver
   */
  public function innerJoin($table, $condition): self
  {
    if ($condition  instanceof DataDriver) {
      $condition = $this->__toString();
      static::$subQuery = "";
    }

    $val = (!empty(static::$subQuery)) ? "subQuery" : "query";

    if (!is_array($table) && !is_string($table)) throw new Exception("The " + gettype($table) + " type is not accepted");
    if (is_array($table)) join(" ", $table);

    static::$$val .= "INNER JOIN $table ON $condition";
    return $this;
  }

  /**
   * @param array|string $columns
   * @param string $orderBy
   * @return \Datadriver\DataDriver 
   */
  public function orderBy($columns, string $orderBy = "ASC"): self
  {
    $val = (!empty(static::$subQuery)) ? "subQuery" : "query";

    if (!is_array($columns) && !is_string($columns)) throw new Exception("The " + gettype($columns) + " type is not accepted");
    if (is_array($columns)) $columns = join(",", $columns);

    static::$$val .= " ORDER BY $columns $orderBy ";
    return $this;
  }

  /**
   * @param int $row_count
   * @param int $offset
   * @return \Datadriver\DataDriver 
   */
  public function limit(int $row_count, int $offset = 0): self
  {
    $val = (!empty(static::$subQuery)) ? "subQuery" : "query";

    static::$$val .= "LIMIT $row_count ";
    if ($offset !== 0) {
      static::$$val .= "OFFSET $offset";
    }
    return $this;
  }
}
