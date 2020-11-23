<?php

namespace Datadriver;

use Datadriver\Helpers\Exceptions\MessageException;
use Datadriver\Helpers\Exceptions\MessageRuntimeException;
use PDO, PDOException, Exception;
use Datadriver\Helpers\Traits\{ConnectionDriver, DataClause, DataImplements, RegisterFunctionsCollection};
use Illuminate\Support\Collection;

class DataDriver
{
  use ConnectionDriver, DataClause, DataImplements, RegisterFunctionsCollection;
  private static ?PDO $pdo = null;
  protected static ?Collection $data = null;
  private static $stmt;
  private static ?string $query = null;
  private static ?string $subQuery = null;
  private static ?string $columns;
  private static ?string $table;
  private static $pdoResult = null;

  public function __construct()
  {
    $this->registerMacros();
    $this->setCollection([]);
    set_error_handler(fn(...$exception) => (new MessageRuntimeException([$exception]))->sendMessage());
    set_exception_handler(fn($exception) => (new MessageException($exception))->sendMessage());
  }

  public function __destruct()
  {
    $this->close();
  }

  public function __wakeup()
  {
    $this->open();
  }

  /**
   * @return string
   */
  private function toString(): string
  {
    return static::$subQuery;
  }

  /**
   * Close connection
   * @return void
   */
  public function close(): void
  {
    if (!is_null(static::$pdo)) {
      static::$pdo = null;
      static::$data = null;
      static::$stmt = null;
      static::$query = null;
      static::$subQuery = null;
    }
  }

  /**
   * @return \DataDriver\DataDriver
   */
  public function open(): self
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
  public function select($table)
  {
    if (!is_array($table) && !is_string($table)) throw new Exception("The " + gettype($table) + " type is not accepted");

    if (is_array($table)) $table  = join(" ", $table);

    $val = (empty(static::$query)) ? "query" : "subQuery";

    static::$$val = "SELECT {columns} FROM $table "; 

    return $this;
  }

  /**
   * @param string $table
   * @param mixed $values
   * @return \DataDriver\DataDriver
   */
  public function insert(string $table, ...$values): self
  {
    $this->setCollection($values);

    $val = (empty(static::$query)) ? "query" : "subQuery";

    static::$$val = "INSERT INTO $table ({columns}) VALUES({vals}) ";
    return $this;
  }

  /**
   * @param string $table
   * @param mixed $values
   * @return \DataDriver\DataDriver
   */
  public function update(string $table, ...$values): self
  {
    $this->setCollection($values);

    $val = (empty(static::$query)) ? "query" : "subQuery";

    static::$$val = "UPDATE $table SET {updateColumns} ";
    return $this;
  }

  /**
   * @param string $table
   * @return \DataDriver\DataDriver
   */
  public function delete(string $table): self
  {
    $val = (empty(static::$query)) ? "query" : "subQuery";

    static::$$val = "DELETE FROM $table ";

    return $this;
  }

  /**
   * @param null|string $columns
   * @return \DataDriver\DataDriver
   */
  public function columns(?string $columns = null): self
  {
    $val = (!empty(static::$subQuery)) ? "subQuery" : "query";

    if (preg_match("/({vals}|{updateColumns})/", static::$$val)) {
      if (empty($columns)) throw new Exception("The insert and update statement requires the names of the column (s).");
    }

    if (empty($columns)) static::$$val = preg_replace("/{columns}/", "*", static::$$val);

    static::$columns = preg_quote($columns, "/ ");

    if (preg_match("/{vals}/", static::$$val)) {
      $columnsArray = explode(",", $columns);
      $preValue = join(",", array_fill(0, count($columnsArray), "?"));
      static::$$val = preg_replace("/{vals}/", $preValue, static::$$val);
    }

    if (preg_match("/{updateColumns}/", static::$$val)) {
      foreach (explode(",", $columns) as $k => $v) {
        $preColumns[$k] = "{$v} = ?";
      }
      static::$$val = preg_replace("/{updateColumns}/", join(", ", $preColumns), static::$$val);
    }

    static::$$val = preg_replace("/{columns}/", $columns, static::$$val);

    return $this;
  }

  /**
   * @param null|callable $callback
   * @return void|\Illuminate\Support\Collection
   */
  public function fetch(?callable $callback = null)
  {
    try {
      $this->open();

      static::$stmt = static::$pdo->prepare(static::$query);
      static::$data->ifHave([$this, "prepareParam"]);
      static::$stmt->execute();

      static::$pdoResult  = (static::$stmt->rowCount() === 1) ? collect(static::$stmt->fetch(PDO::FETCH_ASSOC)) : collect(static::$stmt->fetchAll());

      if (!empty($callback) && is_callable($callback))
        $callback(static::$pdoResult);
      else
        return static::$pdoResult;
    } catch (Exception | PDOException $ex) {
      throw $ex;
    } finally {
      $this->close();
    }
  }

  /**
   * @param null|callable $callback
   * @return void|\Illuminate\Support\Collection
   */
  public function execTransaction(?callable $callback = null)
  {
    try {
      $this->open();

      static::$stmt = static::$pdo->prepare(static::$query);
      static::$data->ifHave([$this, "prepareParam"]);

      static::$pdo->beginTransaction();

      static::$stmt->execute();

      static::$pdo->commit();

      static::$pdoResult  = (static::$stmt->rowCount() === 1) ? collect(static::$stmt->fetch(PDO::FETCH_ASSOC)) : collect(static::$stmt->fetchAll());

      if (!empty($callback) && is_callable($callback))
        $callback(static::$pdoResult);
      else
        return static::$pdoResult;
    } catch (Exception | PDOException $ex) {
      static::$pdo->rollBack();
      throw $ex;
    } finally {
      $this->close();
    }
  }

  /**
   * @return bool
   */
  public function execute(): bool
  {

    try {
      $this->open();

      static::$stmt = static::$pdo->prepare(static::$query);

      static::$data->ifHave([$this, "prepareParam"]);
      static::$stmt->execute();
      return (static::$stmt->rowCount() > 0) ? true : false;
    } catch (Exception | PDOException $ex) {
      throw $ex;
    } finally {
      $this->close();
    }
  }


  /**
   * @param \IlluminateIlluminate\Support\Collection $collection
   * @return \Datadriver\DataDriver
   */
  protected function prepareParam(Collection $collection): self
  {
    $typeVal = [
      "string" => PDO::PARAM_STR,
      "integer" => PDO::PARAM_INT,
      "boolean" => PDO::PARAM_BOOL,
      "null" => PDO::PARAM_NULL,
    ];

    try {
      $collection
        ->each(function ($vals, $in) use ($typeVal) {
          $in += 1;
          (isset($typeVal[gettype($vals)])) ?
            static::$stmt->bindParam($in, $vals, $typeVal[gettype($vals)]) :
            static::$stmt->bindParam($in, $vals);
        });
    } catch (Exception $ex) {
      throw $ex;
    };

    return $this;
  }

  /**
   * @return void
   */
  private function registerMacros(): void
  {
    try {
      $RegisterFunctions = RegisterFunctionsCollection::class;
      $datadriver = $this;
      foreach (get_class_methods($RegisterFunctions) as $funcName) {
        Collection::macro($funcName, function (...$value) use ($datadriver, $funcName) {
          return $datadriver->$funcName($this, ...$value);
        });
      }
    } catch (Exception $ex) {
      throw $ex;
    }
  }
}
