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
    $this->setValues($values);
    $table = $this->setAlias($table);

    $prop = $this->getQueryOrSubquery("query");


    $this->set($prop, "INSERT INTO $table ({columns}) VALUES({insertValues}) ");

    return $this;
  }

  /**
   * @param array|string $table
   * @return \DataDriver\DataDriver
   */
  public function select($table): self
  {
    $this
      ->getClause("select")
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
    $this->setValues($values);

    $this
      ->getClause("update")
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
      ->getClause("delete")
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

    if (preg_match("/({insertValues}|{updateColumns})/", $this->get($prop))) {
      if (empty($columns)) throw new LengthException("The insert and update statement requires the names of the column (s).");
    }

    $columns = preg_quote($columns, "/ ");


    if (empty($columns)) {
      $replaceQuery = preg_replace("/{columns}/", "*", $this->get($prop));
      $this->method("put", $prop, $replaceQuery);
    } else {
      $this->method("put", $prop, preg_replace("/{columns}/", $columns, $this->get($prop)));
    }

    if (preg_match("/{insertValues}/", $this->get($prop))) {
      $columnsArray = explode(",", $columns);
      $preValue = join(", ", array_fill(0, count($columnsArray), "?"));
      $this->method("put", $prop, preg_replace("/{insertValues}/", $preValue, $this->get($prop)));
    }

    if (preg_match("/{updateColumns}/", $this->get($prop))) {
      foreach (explode(",", $columns) as $k => $v)
        $preColumns[$k] = "{$v} = ?";

      $this->method("put", $prop, preg_replace("/{updateColumns}/", join(", ", $preColumns), $this->get($prop)));
    }

    return $this;
  }
}
