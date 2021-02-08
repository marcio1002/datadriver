<?php

namespace Datadriver\Schema;

use Datadriver\DataDriver, Datadriver\Schema\SyntaxSql, Illuminate\Support\Collection;

trait  Sql
{
  private string $clause;
  private array $sqlSyntax;
  private ?Collection $collection = null;

  public function __construct(Collection $collection, string $clause)
  {
    $this->clause = $clause;
    $this->collection = $collection;
    $this->loadSyntax();
  }

   public function __destruct()
  {
    $this->dispatchClause();
  }

  private function loadSyntax(): self
  {
    $this->sqlSyntax = (new SyntaxSql)->get(static::class);

    foreach ($this->sqlSyntax as $key => $val) $this->collection->put($key, $val);

    return $this;
  }

  protected function getQueryOrSubquery(string $str): string
  {
    return (is_null($this->collection->get($str))) ? "query" : "subQuery";
  }

  protected function dispatchClause(): void
  {
    foreach ($this->sqlSyntax as $key => $val) $this->collection->forget($key);
  }

  protected function replaceClause($strSearch, $value): void
  {
    $clause = $this->collection->get($this->clause);
    $c = 0;
    $replace = "";
    foreach (preg_split("/\s/", $clause) as $v) {
      if (preg_match("/$strSearch/", $v) && $c === 0) {
        $replace .= preg_replace("/$strSearch/", " $value ", $v);
        $c += 1;
      } else
        $replace .= "$v ";
    }

    $query = $this->collection->get(
      $prop = $this->getQueryOrSubquery("subQuery")
    );

    $clause = preg_quote(trim($clause));

    if (preg_match("/($clause)+$/", $query))
      $replaceQuery = trim(preg_replace("/($clause)+$/", " $replace ", $query));
    else
      $replaceQuery = trim("$query $replace ");


    $this->collection->put($prop, $replaceQuery);

    $this->collection->put($this->clause, $replace);
  }

  public function setInstruction(string $table): self
  {
    $prop = $this->getQueryOrSubquery("query");

    $instruction = (new SyntaxSql)->getInstruction($this->clause);
    $instruction = str_replace("{table}", $table, $instruction);

    $this->collection->set($prop, $instruction);
    return $this;
  }

  public function appendValues(...$values): self
  {
    foreach ($values as $value)
      $this->replaceClause("{values}", $value);

    return $this;
  }

  public function appendArgs(...$args): self
  {
    foreach ($args as $arg)
      $this->replaceClause("{args}", $arg);

    return $this;
  }
}
