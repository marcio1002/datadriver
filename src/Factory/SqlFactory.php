<?php

namespace Datadriver\Factory;


class SqlFactory
{

  private array $sqlClass = ["mysql", "pgsql", "sqlite"];
  private string $class;

  public function create($className): self
  {

    if (in_array($className, $this->sqlClass)) {
      $class = "Datadriver\\Schema\\" .  ucfirst($className);
      $this->class = $class;
    }

    return $this;
  }

  /**
   * @param mixed $params
   * @return null|object
   */
  public function addParams(...$params)
  {
    return (class_exists($this->class)) ? new $this->class(...$params) : null;
  }

  public function getClassName(): ?string
  {
    return $this->class ?? null;
  }
}
