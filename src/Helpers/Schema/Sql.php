<?php

namespace Datadriver\Helpers\Schema;

use Illuminate\Support\Collection;

trait Sql
{

  private static $callable = null;
  private static string $clause;
  private static ?Collection $collect = null;
  private static $sqlProperty = [];
  private static $sqlPropertyPolymorphism = [];

  private function setPropertySql(array $sqlClause = []): void
  {
    static::$sqlPropertyPolymorphism =
      [
        "where" => "WHERE {args}",
        "limit" =>  "LIMIT {args} OFFSET {args}",
        "in" => "WHERE {args} IN({values})",
        "and" => "AND {args}",
        "order" => "ORDER BY {args} {args}",

      ]
      +
      $sqlClause;
  }

  protected function initCollect(Collection $collection, array $sqlClause = []): void
  {

    if (count($sqlClause) > 0)
      $this->setPropertySql($sqlClause);
    else
      $this->setPropertySql();

    static::$collect = $collection->merge(static::$sqlPropertyPolymorphism);
  }

  protected function replaceClause($prop, string $clause, $value): void
  {
    $c = 0;
    $replace = "";
    foreach (preg_split("/\s/", $clause) as $v) {
      if (preg_match("/$prop/",$v) && $c === 0) {
        $replace .= "{$value} ";
        $c += 1;
      } else
        $replace .= "$v ";
    }
    static::$collect->put(static::$clause, trim($replace));
  }

  public function appendValues(...$values): self
  {
    $clause = static::$collect->get(static::$clause);
    $callable = static::$callable;

    foreach ($values as $value)
      $this->replaceClause("{values}", $clause, $value);
      
    $callable(static::$collect->get(static::$clause));

    return $this;
  }

  public function appendArgs(...$args): self
  {
    $clause = static::$collect->get(static::$clause);
    $callable = static::$callable;

    foreach ($args as $arg)
      $this->replaceClause("{args}", $clause, $arg);

    $callable(static::$collect->get(static::$clause));

    return $this;
  }
}
