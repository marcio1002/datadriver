<?php

namespace Datadriver\Traits;

use InvalidArgumentException;

trait DriverClause
{
  /**
   * @var static $sql
   */

  /**
   * @param string $column
   * @param string $operator
   * @param mixed $values
   * @return \DataDriver\DataDriver
   */
  public function where(string $column, string $operator, $values): self
  {

    $this
      ->setValues([$values])
      ->syntax("where")
      ->appendArgs("$column $operator ?");

    return $this;
  }

  /**
   * @param string $column
   * @param string $operator
   * @param mixed $values
   * @return \DataDriver\DataDriver
   */
  public function having(string $column, string $operator, $values): self
  {
    $this
      ->setValues([$values])
      ->syntax("having")
      ->appendArgs("$column $operator ?");

    return $this;
  }

  /**
   * @param string $column
   * @param string $operator
   * @param mixed $values
   * @return \DataDriver\DataDriver
   */
  public function and(string $column, string $operator, $values): self
  {
    $this
      ->setValues([$values])
      ->syntax("and")
      ->appendArgs("$column $operator ?");

    return $this;
  }

  /**
   * @param string $column
   * @param string $operator
   * @param mixed $values
   * @return \DataDriver\DataDriver
   */
  public function or(string $column, string $operator, $values): self
  {
    $this
      ->setValues([$values])
      ->syntax("or")
      ->appendArgs("$column $operator ?");

    return $this;
  }

  /**
   * @param string $column
   * @param mixed|\DataDriver\DataDriver ...$values
   * @return \DataDriver\DataDriver
   */
  public function In(string $column, ...$values): self
  {
    if ($values[0] = $this->isInstanceofDatadriver($values)) {
      $this
        ->syntax("in")
        ->appendArgs($column)
        ->appendValues(join(",", $values));

    } else {
      $preValues = array_fill(0, count($values), "?");

      $this
        ->setValues($values)
        ->syntax("in")
        ->appendArgs($column)
        ->appendValues(join(", ", $preValues));
    }

    return $this;
  }

  /**
   * @param string $column
   * @param  mixed|\DataDriver\DataDriver ...$values
   * @return \DataDriver\DataDriver
   */
  public function notIn(string $column, ...$values): self
  {
    if ($values[0] = $this->isInstanceofDatadriver($values)) {
      $this
        ->syntax("notIn")
        ->appendArgs($column)
        ->appendValues(join(",", $values));

    } else {
      $preValues = join(", ",array_fill(0, count($values), "?"));

      $this
        ->setValues($values)
        ->syntax("notIn")
        ->appendArgs($column)
        ->appendValues($preValues);
    }

    return $this;
  }

  /**
   * @param array|string $table
   * @param ...$mixed $condition
   * @return \DataDriver\DataDriver
   */
  public function join($table, ...$condition): self
  {
    if (count($condition) > 3) throw new InvalidArgumentException("Expected a maximum of 3 parameters");

    if(isset($condition[2])) {
      $this->setValues([$condition[2]]);
      $condition[2] = "?";
    }

    $this
      ->syntax("join")
      ->appendArgs( $this->setAlias($table),  join(" ", $condition) );


    return $this;
  }

  /**
   * @param array|string|int $columns
   * @param string $orderBy
   * @return \Datadriver\DataDriver 
   */
  public function orderBy($columns, string $orderBy = "ASC"): self
  {
    if (!is_array($columns) && !is_string($columns) && !is_int($columns)) throw new InvalidArgumentException("The " + gettype($columns) + " type is not accepted");
    if (is_array($columns)) $columns = join(", ", $columns);

    $this
      ->syntax("order")
      ->appendArgs($columns, $orderBy);

    return $this;
  }

  /**
   * @param ...$mixed $columns
   * @return \Datadriver\DataDriver 
   */
  public function groupBy(...$columns): self 
  {
    $this
      ->syntax("group")
      ->appendArgs(join(", ", $columns));

    return $this;
  }

  /**
   * @param int $row_count
   * @param int $offset
   * @return \Datadriver\DataDriver 
   */
  public function limit(int $row_count, int $offset = 0): self
  {
    $this
      ->syntax("limit")
      ->appendArgs($row_count, $offset);

    return $this;
  }
}
