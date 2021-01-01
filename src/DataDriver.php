<?php

namespace Datadriver;

use PDO, PDOException, Exception, Illuminate\Support\Collection, Datadriver\Helpers\ImplementsHelper;
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
  private $stmt = null;
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
    return $this->method("get","query") ?? DataDriver::class;
  }

  /**
   * Close connection
   * @return void
   */
  public function close(): void
  {
    foreach (["pdo", "stmt"] as $props)
      if (!is_null($this->$props)) $this->$props = null;
  }

  /**
   * @return \DataDriver\DataDriver
   */
  public function open(): self
  {
    if (!isset($this->pdo) || is_null($this->pdo)) {
      $this->pdo = $this->getConnection();
    }
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

      $this->stmt = $this->pdo->prepare($this->sql->query);
      $this->data->ifHave([$this, "prepareParam"]);
      $this->stmt->execute();

      $this->pdoResult  = ($this->stmt->rowCount() === 1) ? collect($this->stmt->fetch(PDO::FETCH_ASSOC)) : collect($this->stmt->fetchAll());

      if (!empty($callback) && is_callable($callback))
        $callback($this->pdoResult);
      else
        return $this->pdoResult;
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

      $this->stmt = $this->pdo->prepare($this->sql->query);
      $this->sql->method("ifHave", [$this, "prepareParam"]);

      $this->pdo->beginTransaction();

      $this->stmt->execute();

      $this->pdo->commit();

      $this->pdoResult  = ($this->stmt->rowCount() === 1) ? collect($this->stmt->fetch(PDO::FETCH_ASSOC)) : collect($this->stmt->fetchAll());

      if (!empty($callback) && is_callable($callback))
        $callback($this->pdoResult);
      else
        return $this->pdoResult;
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
  public function execute(): bool
  {

    try {
      $this->open();

      $this->stmt = $this->pdo->prepare($this->sql->query);

      $this->data->ifHave([$this, "prepareParam"]);
      $this->stmt->execute();
      return ($this->stmt->rowCount() > 0) ? true : false;
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
    try {
      $typeVal = [
        "string" => PDO::PARAM_STR,
        "integer" => PDO::PARAM_INT,
        "boolean" => PDO::PARAM_BOOL,
        "null" => PDO::PARAM_NULL,
      ];
      $collection
        ->each(function ($vals, $in) use ($typeVal) {
          $in += 1;
          isset($typeVal[gettype($vals)]) ?
            $this->stmt->bindParam($in, $vals, $typeVal[gettype($vals)]) :
            $this->stmt->bindParam($in, $vals);
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
    $datadriver = $this;
    foreach (get_class_methods(RegisterFunctionsCollection::class) as $funcName)
      Collection::macro($funcName, fn (...$value) => $datadriver->$funcName($this, ...$value));
  }
}
