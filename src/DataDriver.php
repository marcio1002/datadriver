<?php

namespace Datadriver;

use PDO, PDOStatement, PDOException, Exception, Illuminate\Support\Collection, Datadriver\Helpers\ImplementsHelper;
use Datadriver\Traits\{ConnectionDriver, DriverClause, InstructionSql, RegisterFunctionsCollection};
use Datadriver\Exceptions\{MessageException, MessageError};

class DataDriver
{
  use
    ConnectionDriver,
    DriverClause,
    InstructionSql,
    RegisterFunctionsCollection;

  private ?PDO $pdo = null;
  private ?PDOStatement $stmt = null;
  private $pdoResult = null;

  public function __construct(bool $msgHandlerErro = true)
  {
    $this->__init();
    $this->registerMacros();

    if ($msgHandlerErro) {
      set_error_handler(fn (...$exception) => (new MessageError($exception)));
      set_exception_handler(fn ($exception) => (new MessageException($exception)));
    }
  }

  public function __destruct()
  {
    $this->close();
  }

  public function __wakeup()
  {
    $this->open();
  }

  public function __toString()
  {
    return $this->method("get", "query") ?? DataDriver::class;
  }

  /**
   * Close connection
   * @return void
   */
  public function close(): void
  {
    foreach ($this as $k => $props)
      if (!is_null($this->$k)) $this->$k = null;

    $this->method("pull","values");
    $this->method("pull","query");
  }

  /**
   * @return \DataDriver\DataDriver
   */
  public function open(): self
  {
    if (is_null($this->pdo)) {
      $this->pdo = $this->getConnection();
    }
    return $this;
  }

  /**
   * Essa função foi cria da para ser  utilizada em dois métodos
   * fetch e fetchTransaction
   * 
   * @param null|callable $callback
   * @return void|\Illuminate\Support\Collection
   */
  public function execute(?callable $callback = null) 
  {
      $this->stmt = $this->pdo->prepare($this->method("get", "query"));

      if (!is_null($this->method("get", "values")))
        $this->prepareParam();

      $this->stmt->execute();
      $this->pdoResult  = ($this->stmt->rowCount() === 1) ? collect($this->stmt->fetch(PDO::FETCH_ASSOC)) : collect($this->stmt->fetchAll());

      if (!empty($callback) && is_callable($callback))
        $callback($this->pdoResult);
      else
        return $this->pdoResult;
  }


  /**
   * @param null|callable $callback
   * @return void|\Illuminate\Support\Collection
   */
  public function fetch(?callable $callback = null)
  {
    try {
      $this->open();

      return $this->execute($callback);

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
  public function fetchTransaction(?callable $callback = null)
  {
    try {
      $this->open();

      $this->pdo->beginTransaction();

      return $this->execute($callback);

      $this->pdo->commit();
      
    } catch (Exception | PDOException $ex) {
      $this->pdo->rollBack();
      throw $ex;
    } finally {
      $this->close();
    }
  }


  /**
   * @return bool
   */
  public function exec(): bool
  {

    try {
      $this->open();

      $this->stmt = $this->pdo->prepare($this->method("get", "query"));

      if (!is_null($this->method("get", "values")))
        $this->prepareParam();

      $this->stmt->execute();
      return ($this->stmt->rowCount() != 0) ? true : false;

    } catch (Exception | PDOException $ex) {
      throw $ex;
    } finally {
      $this->close();
    }
  }

  /**
   * @param string $query
   * @param mixed ...$values
   * @return void
   */
  public function raw(string $query, ...$values)
  {
    try {
      $this->method("put", "query", $query);
      $this->method("put", "values", $values);
      
    } catch (Exception | PDOException $ex) {
      throw $ex;
    }
  }


  /**
   * @param \IlluminateIlluminate\Support\Collection $collection
   * @return \Datadriver\DataDriver
   */
  protected function prepareParam(): self
  {
    try {
      $typeVal = [
        "string" => PDO::PARAM_STR,
        "integer" => PDO::PARAM_INT,
        "boolean" => PDO::PARAM_BOOL,
        "null" => PDO::PARAM_NULL,
      ];

      $values = $this->method("get", "values");
      $values = is_array($values) ? $values : [$values];

      foreach ($values as $i => &$v)
        isset($typeVal[gettype($v)]) ?
          $this->stmt->bindParam($i + 1, $v, $typeVal[gettype($v)]) :
          $this->stmt->bindParam($i + 1, $v);
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
    $datadriver = $this;
    foreach (get_class_methods(RegisterFunctionsCollection::class) as $funcName)
      Collection::macro($funcName, fn (...$value) => $datadriver->$funcName($this, ...$value));
  }
}
