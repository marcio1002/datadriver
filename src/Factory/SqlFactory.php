<?php

namespace Datadriver\Factory;


class SqlFactory
{

  private array $sqlClass = ["mysql", "pgsql", "sqlite"];
  private string $class;

  public function create(): self
  {
    $className = strtolower(DB_CONFIG["DRIVE"]);

    if (in_array($className, $this->sqlClass)) 
      $this->class = "Datadriver\\Schema\\" .  ucfirst($className);

    return $this;
  }

  /**
   * @param mixed $params
   * @return null|\Datadriver\Schema\[Mysql,Pgsql,Sqlite]
   */
  public function addParams(...$params): ?object
  {
    return (class_exists($this->class)) ? new $this->class(...$params) : null;
  }

  public function getClassName(): ?string
  {
    return $this->class ?? null;
  }
}
