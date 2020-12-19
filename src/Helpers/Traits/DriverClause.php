<?php

namespace Datadriver\Helpers\Traits;

use Datadriver\DataDriver, Exception;

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
    $prop = (is_null($this->sql->subQuery)) ? "query" : "subQuery";

    $this->sql
      ->setValues([$values])
      ->getClause(!stripos($this->sql->$prop, "WHERE") ? "where" : "and")
      ->appendArgs("$column $operator ?");

    return $this;
  }

  /**
   * @param string| $column
   * @param mixed|\DataDriver\DataDriver ...$values
   * @return \DataDriver\DataDriver
   */
  public function whereIn($column, ...$values): self
  {
    if ($values[0] = $this->sql->isInstanceofDatadriver($values)) {
      $this->sql
        ->getClause("in")
        ->appendArgs($column)
        ->appendValues(join(",", $values));
    } else {
      $values = array_fill(0, count($values), "?");

      $this->sql
        ->setValues($values)
        ->getClause("in")
        ->appendArgs($column)
        ->appendValues(join(",", $values));
    }

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
      $values[0] = $this->toString();
    } else {
      $this->setCollection($values);
      $values = array_fill(0, count($values), "?");
    }

    $val = (!empty($this->subQuery)) ? "subQuery" : "query";

    $values = join(",", $values);

    if (stripos($this->$val, "WHERE"))
      $this->val .= " AND $column  NOT IN ($values)";
    else
      $this->val .= " WHERE $column NOT IN ($values)";
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
    } else {
      if (count($expression) > 3) throw new Exception("Expected a maximum of 3 parameters");

      $this->setCollection($expression[2]);
      $expression[2] = "?";
      $expression =  join(" ", $expression);
    }


    if ($expression2 instanceof DataDriver) {
      $expression2 = $this->toString();
    } else {
      if (count($expression2) > 3) throw new Exception("Expected a maximum of 3 parameters");

      $this->setCollection([array_pop($expression2)]);
      $expression2[2] = "?";
      $expression2 =  join(" ", $expression2);
    }


    $val = (!empty($this->subQuery)) ? "subQuery" : "query";

    if (stripos($this->val, "WHERE"))
      $this->val .= "AND $expression OR $expression2 ";
    else
      $this->val .= "WHERE $expression OR $expression2";

    return $this;
  }

  /**
   * @param array|string $table
   * @param array|\DataDriver\DataDriver $condition
   * @return \DataDriver\DataDriver
   */
  public function innerJoin($table, ...$condition): self
  {
    if ($condition  instanceof DataDriver) {
      $condition = $this->toString();
    } else {
      if (count($condition) > 3) throw new Exception("Expected a maximum of 3 parameters");
      $this->setCollection([$condition[2]]);
      $condition[2] = "?";
      $condition = join(" ", $condition);
    }

    $val = (!empty($this->subQuery)) ? "subQuery" : "query";

    if (!is_array($table) && !is_string($table)) throw new Exception("The " + gettype($table) + " type is not accepted");
    if (is_array($table)) join(" ", $table);

    $this->val .= "INNER JOIN $table ON $condition";
    return $this;
  }

  /**
   * @param array|string|int $columns
   * @param string $orderBy
   * @return \Datadriver\DataDriver 
   */
  public function orderBy($columns, string $orderBy = "ASC"): self
  {
    if (!is_array($columns) && !is_string($columns) && !is_int($columns)) throw new Exception("The " + gettype($columns) + " type is not accepted");
    if (is_array($columns)) $columns = join(",", $columns);

    $this->sql
      ->getClause("order")
      ->appendArgs($columns, $orderBy);

    return $this;
  }

  /**
   * @param int $row_count
   * @param int $offset
   * @return \Datadriver\DataDriver 
   */
  public function limit(int $row_count, int $offset = 0): self
  {
    $val = (!empty($this->subQuery)) ? "subQuery" : "query";

    $this->val .= "LIMIT $row_count ";
    if ($offset !== 0) {
      $this->val .= "OFFSET $offset";
    }
    return $this;
  }
}
