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

  protected function get(string $key)
  {
    return static::$collect->get($key);
  }

  protected function method(string $name, ...$arguments)
  {
    return static::$collect->$name(...$arguments);
  }

  protected function getQueryOrSubquery(string $str): string
  {
    return (is_null($this->get($str))) ? "query" : "subQuery";
  }

  protected function setValues(array $mixed): self
  {
    foreach ($mixed as  $val) {
      if (is_object($val)) $this->setValues($this->toArray($val));
      if (is_array($val)) $this->setValues($val);
      static::$collect->set("values", $val);
    }

    return $this;
  }

  /**
   * @param object $object
   *  The object to be converted
   * @return array
   */
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

    return (is_array($value)) ? join(" ", $value) : $value;
  }

  /**
   * @param string $clause
   * @return null|object
   */
  protected function getClause(string $clause): ?object
  {
    return 
    (new SqlFactory)
      ->create("mysql")
      ->addParams(static::$collect, $clause);

  }

  /**
   * @param mixed $var
   * @return null|string
   */
  public function isInstanceofDatadriver($var): ?string
  {
    if (is_array($var) && collect($var)->contains(fn ($v) => ($v instanceof DataDriver)))
      $subQuery = $this->get("subQuery");
    elseif ($var instanceof DataDriver)
      $subQuery = $this->get("subQuery");

    $this->method("put","subQuery", null);

    return $subQuery ?? null;
  }
  
}
