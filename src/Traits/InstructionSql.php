<?php

namespace Datadriver\Traits;

use
  Datadriver\Helpers\ImplementsHelper,
  LengthException;

trait InstructionSql
{

  use ImplementsHelper;

  /**
   * @param array|string $table
   * @param mixed $values
   * @return \DataDriver\DataDriver
   */
  public function insert($table, ...$values): self
  {
    $this
      ->setValues($values)
      ->syntax("insert")
      ->setIntruction($this->setAlias($table));

    return $this;
  }

  /**
   * @param array|string $table
   * @return \DataDriver\DataDriver
   */
  public function select($table): self
  {
    $this
      ->syntax("select")
      ->setInstruction($this->setAlias($table));

    return $this;
  }

  /**
   * @param array|string $table
   * @param mixed $values
   * @return \DataDriver\DataDriver
   */
  public function update($table, ...$values): self
  {
    $this
      ->setValues($values)
      ->syntax("update")
      ->setInstruction($this->setAlias($table));

    return $this;
  }

  /**
   * @param array|string $table
   * @return \DataDriver\DataDriver
   */
  public function delete(string $table): self
  {
    $this
      ->syntax("delete")
      ->setInstruction($this->setAlias($table));

    return $this;
  }

  /**
   * @param null|string $columns
   * @return \DataDriver\DataDriver
   */
  public function columns(?string $columns = null): self
  {
    $prop = $this->getQueryOrSubquery("subQuery");

    if (preg_match("/({insertValues}|{updateColumns})/", $this->method("get", $prop))) {
      if (empty($columns)) throw new LengthException("The instruction insert and update statement requires the names of the column(s).");
    }

    if (empty($columns)) 
      $this->method("put", $prop, preg_replace("/{columns}/", "*", $this->method("get", $prop)));
     else 
      $this->method("put", $prop, preg_replace("/{columns}/", $columns, $this->method("get", $prop)));
    

    if (preg_match("/{insertValues}/", $this->method("get", $prop))) {
      $columnsArray = explode(",", $columns);
      $preValue = join(", ", array_fill(0, count($columnsArray), "?"));
      $this->method("put", $prop, preg_replace("/{insertValues}/", $preValue, $this->method("get", $prop)));
    }

    if (preg_match("/{updateColumns}/", $this->method("get", $prop))) {
      foreach (explode(",", $columns) ?? [] as $k => $v)
        $preColumns[$k] = "{$v} = ?";

      $this->method("put", $prop, preg_replace("/{updateColumns}/", join(", ", $preColumns), $this->method("get", $prop)));
    }

    return $this;
  }
}
