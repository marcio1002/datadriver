<?php

namespace Datadriver\Helpers\Traits;

use 
  Datadriver\DataDriver,
  Illuminate\Support\Collection, 
  Datadriver\Helpers\Traits\ImplementsHelper, 
  InvalidArgumentException, 
  LengthException;

class InstructionSqlHelper
{

  use ImplementsHelper;

  private static $collect;

  public function __construct()
  {
    if (!(static::$collect instanceof Collection)) static::$collect = collect();
  }

  public function __set($key, $mixed): void
  {
    static::$collect->set($key, $mixed);
  }

  public function __get(string $key)
  {
    return static::$collect->get($key);
  }

  public function select($table): self
  {
    $prop = (is_null($this->query)) ? "query" : "subQuery";

    $this->$prop = "SELECT {columns} FROM $table ";

    return $this;
  }

  public function insert(string $table): self
  {
    $prop = (is_null($this->query)) ? "query" : "subQuery";

    $this->$prop = "INSERT INTO $table ({columns}) VALUES({vals}) ";

    return $this;
  }

  public function update(string $table): self
  {
    $prop = (is_null($this->query)) ? "query" : "subQuery";

    $this->$prop = "UPDATE $table SET {updateColumns} ";

    return $this;
  }

  public function delete(string $table): self
  {
    $prop = (is_null($this->query)) ? "query" : "subQuery";

    $this->$prop = "DELETE FROM $table";

    return $this;
  }

  public function columns(?string $columns = null): self
  {
    $prop = (is_null($this->subQuery)) ? "query" : "subQuery";

    if (preg_match("/({vals}|{updateColumns})/", $this->$prop)) {
      if (empty($columns)) throw new LengthException("The insert and update statement requires the names of the column (s).");
    }

    $columns = preg_quote($columns, "/ ");


    if (empty($columns)) {
      $replaceQuery = preg_replace("/{columns}/", "*", $this->$prop);
      static::$collect->put($prop, $replaceQuery);
    } else {
      static::$collect->put($prop, preg_replace("/{columns}/", $columns, $this->$prop));
    }

    if (preg_match("/{vals}/", $this->$prop)) {
      $columnsArray = explode(",", $columns);
      $preValue = join(",", array_fill(0, count($columnsArray), "?"));
      static::$collect->put($prop, preg_replace("/{vals}/", $preValue, $this->$prop));
    }

    if (preg_match("/{updateColumns}/", $this->$prop)) {
      foreach (explode(",", $columns) as $k => $v) {
        $preColumns[$k] = "{$v} = ?";
      }
      static::$collect->put($prop, preg_replace("/{updateColumns}/", join(", ", $preColumns), $this->$prop));
    }

    return $this;
  }

  public function call(string $name, ...$arguments)
  {
    return static::$collect->$name(...$arguments);
  }

  /**
   * @param string|array $value
   */
  public function setAlias($value): string
  {
    if (!is_array($value) && !is_string($value)) throw new InvalidArgumentException("The " + gettype($value) + " type is not accepted");

    return (is_array($value)) ? join(" ", $value) : $value;
  }

  public function setValues(array $mixed): self
  {
    foreach ($mixed as  $val) {
      if (is_object($val)) static::$collect->set("values", $this->toArray($val));
      if (is_array($val)) static::$collect->set("values", $val);
      static::$collect->set("values", $val);
    }

    return $this;
  }

  /**
   * @param string $clause
   * @return null|object
   */
  public function getClause(string $clause)
  {
    if(!in_array(DB_CONFIG["DRIVE"],["mysql","pgsql","sqlite"])) goto last;

    $class = "Datadriver\\Helpers\\Schema\\" . ucfirst(DB_CONFIG["DRIVE"]);
    $InstructionSqlHelper = $this;
  
    if (class_exists($class)) {
      return new $class(static::$collect, $clause, function ($query) use($InstructionSqlHelper) {
        $prop = (is_null($InstructionSqlHelper->subQuery)) ? "query" : "subQuery";
        static::$collect->put($prop, "{$InstructionSqlHelper->$prop} $query");
      });
    }

    last:
    return null;
  }

  /**
    * @param mixed $var
    * @return string|null
   */
  public function isInstanceofDatadriver($var)
  {
    if(is_array($var) && collect($var)->contains(fn ($v) => ($v instanceof DataDriver))) 
      $subQuery = $this->subQuery;

    elseif($var instanceof DataDriver)
      $subQuery = $this->subQuery;

    static::$collect->put("subQuery",null);

    return $subQuery ?? null;
  }


}
