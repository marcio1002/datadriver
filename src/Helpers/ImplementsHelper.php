<?php

namespace Datadriver\Helpers;

use 
  Datadriver\DataDriver,
  InvalidArgumentException,
  Datadriver\Factory\SqlFactory,
  Illuminate\Support\Collection;

trait ImplementsHelper
{

  private static $collect;

  protected function __init()
  {
    if (!(static::$collect instanceof Collection)) static::$collect = collect();
  }

  protected function method(string $name, ...$arguments)
  {
    return static::$collect->$name(...$arguments);
  }

  protected function getQueryOrSubquery(string $str): string
  {
    return (is_null($this->method("get",$str))) ? "query" : "subQuery";
  }

  protected function setValues(array $mixed): self
  {
    foreach ($mixed as  $val) {
      if (is_object($val)) $this->setValues($this->toArray($val));
      if (is_array($val)) $this->setValues($val);
      $this->method("set","values", $val);
    }

    return $this;
  }

  protected function toArray(object $object): array
  {
    $classAnonymous = new class{};

    $classAnonymous->object = $object;
    $array = [];

    foreach ($classAnonymous->object as $property => $val) $array[$property] = $val;

    return $array;
  }

  /**
   * @param string|array $value
   * @return string
   */
  protected function setAlias($value): string
  {
    if (!is_array($value) && !is_string($value)) throw new InvalidArgumentException("The " + gettype($value) + " type is not accepted");

    $driver = (strtolower(DB_CONFIG["DRIVE"]) <=> "sqlite") === 0 ? " AS " : " ";

    return (is_array($value)) ? join($driver, $value) : $value;
  }

  /**
   * @param string $clause
   * @return null|\Datadriver\Schema\[Mysql,Pgsql,Sqlite]
   */
  protected function syntax(string $clause): ?object
  {
    return 
    (new SqlFactory)
      ->create()
      ->addParams(static::$collect, $clause);
  }

  /**
   * @param mixed $var
   * @return null|string
   */
  public function isInstanceofDatadriver($var): ?string
  {
    if (is_array($var) && collect($var)->contains(fn ($v) => ($v instanceof DataDriver)))
      $subQuery = $this->method("get","subQuery");
    elseif ($var instanceof DataDriver)
      $subQuery = $this->method("get","subQuery");

    $this->method("pull","subQuery");

    return $subQuery ?? null;
  }
  
}
