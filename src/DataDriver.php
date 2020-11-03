<?php

namespace Datadriver;

use PDO, PDOException, Exception;
use Datadriver\Traits\{ConnectionDriver,DataClause,DataImplements};

class DataDriver
{
  use ConnectionDriver,DataClause,DataImplements;
  private static PDO $pdo;
  private static $stmt = null;
  private static string $query;
  private static string $columns;
  private static string $table;
  private static $data;

  public function __destruct()
  {
    static::close();
  }

  /**
   * Close connection
   * @return void
   */
  public function close(): void
  {
    if (isset(static::$pdo)) {
      unset($stmt);
      unset($query);
    }
  }

  public function open(): DataDriver
  {
    if (!isset(static::$pdo) || static::$pdo === null) {
      static::$pdo = $this->getConnection();
    }
    return $this;
  }

  /**
   * @param array|string $table
   * @return \DataDriver\DataDriver
   */
  public function select($table): DataDriver
  {
    if(!is_array($table) && !is_string($table)) throw new Exception("The " + gettype($table) + " type is not accepted");

    if(is_array($table)) $table  = join(" ",$table);
    static::$query = "SELECT {columns} FROM $table ";
    return $this;
  }

  public function insert(string $table, $values): DataDriver
  {
    static::$query = "INSERT INTO $table ({columns}) VALUES({vals}) ";
    return $this;
  }

  public function update(string $table, $values): DataDriver
  {
    static::$query = "UPDATE $table SET {updateColumns} ";
    return $this;
  }

  public function delete(string $table): DataDriver
  {
    static::$query = "DELETE FROM $table ";
    return $this;
  }
  
  public function columns(string $columns = null): DataDriver
  {
    if (empty($columns)) static::$query = preg_replace("/{columns}/", "*", static::$query);

    static::$columns = preg_quote($columns, "/");

    if(preg_match("/{vals}/",static::$query)) {
      $columnsArray = explode(",", $columns);
      $preValue = join(",", array_fill(0, count($columnsArray), "?"));
      static::$query = preg_replace("/{vals}/",$preValue,static::$query);
    }

    if(preg_match("/{updateColumns}/",static::$query)) {
      foreach(explode(",", $columns) as $v) {
        $columns .= "{$v} = ?";
      }
      static::$query = preg_replace("{updateColumns}",$columns, static::$query);
    }

    static::$query = preg_replace("/{columns}/", $columns, static::$query);
    
    return $this;
  }

  /**
   * @param string $where
   * @param mixed ...$values
   */
  public function where(string $where, ...$values): DataDriver
  {
    foreach($values as $k => $val) {
      if(is_object($val)) return static::$data = $this->toArray($val);
      if(is_array($val)) return static::$data = $val;
      static::$data[$k] = $val;
    }

    if(stristr(static::$query,"WHERE")) 
      static::$query .= " AND $where ";
    else 
      static::$query .= " WHERE $where ";

    return $this;
  }

  /**
   * @param null|callable $callback
   * @return null|array
   */
  public function fetch(?callable $callback = null)
  { 
    try{
      $this->open();

      static::$stmt = static::$pdo->prepare(static::$query);
      if(!empty(static::$data)) {
        $this->prepareParam(static::$data);
      }
      static::$stmt->execute();

      static::$data  = (static::$stmt->rowCount() === 1) ? static::$stmt->fetch(PDO::FETCH_ASSOC) : static::$stmt->fetchAll();

      if(!empty($callback) && is_callable($callback)) 
        $callback(static::$data);
      else
        return static::$data ?? null;

    }catch(Exception | PDOException $ex) {
      throw $ex;
    }finally {
      $this->close();
    }
  }

  public function execTransaction()
  {
    try{
      static::$stmt = static::$pdo->prepare(static::$query);
      static::$pdo->beginTransaction();

    }catch(Exception | PDOException $ex) {
      throw $ex;
    } 
  }

  public function execute(): bool
  {
    return false;
  }


  private function prepareParam(array $vals, ?array $data_type = []): DataDriver
  {
    $typeVal = [
      "string" => PDO::PARAM_STR,
      "integer" => PDO::PARAM_INT,
      "boolean" => PDO::PARAM_BOOL,
      "null" => PDO::PARAM_NULL,
    ];
    $c = 0;
    foreach ($vals as &$v) {
      $c += 1;
      $v = preg_quote($v, "/");

      if (isset($data_type[$c - 1])) {
        static::$stmt->bindParam($c, $v, $data_type[$c - 1]);
      } else {
        (isset($typeVal[gettype($v)])) ?
          static::$stmt->bindParam($c, $v,$typeVal[gettype($v)]) :
          static::$stmt->bindParam($c, $v);
      }
    }
    return $this;
  }
}
