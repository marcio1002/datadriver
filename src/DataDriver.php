<?php

namespace Datadriver;

use Datadriver\Helpers\Exceptions\MessageException, Datadriver\Helpers\Exceptions\MessageRuntimeException;
use PDO, PDOException, Exception, Illuminate\Support\Collection;
use Datadriver\Helpers\Traits\{ConnectionDriver, DriverClause, ImplementsHelper, InstructionSqlHelper, RegisterFunctionsCollection};

class DataDriver
{
  use ConnectionDriver, DriverClause, ImplementsHelper, RegisterFunctionsCollection;
  private ?PDO $pdo = null;
  private ?InstructionSqlHelper $sql = null;
  private $stmt = null;
  private $pdoResult = null;

  public function __construct(bool $msgHandlerErro = true)
  {
    $this->registerMacros();
    $this->sql = new InstructionSqlHelper;
    if ($msgHandlerErro) {
      set_error_handler(fn (...$exception) => (new MessageRuntimeException($exception))->sendMessage());
      set_exception_handler(fn ($exception) => (new MessageException($exception))->sendMessage());
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

  /**
   * @return string
   */
  private function toString(): string
  {
    return $this->sql->subQuery;
  }

  /**
   * Close connection
   * @return void
   */
  public function close(): void
  {
    foreach (["pdo", "sql", "stmt"] as $props)
      if (!is_null($this->$props)) $this->$props = null;
  }

  /**
   * @return \DataDriver\DataDriver
   */
  public function open(): self
  {
    if (!isset($this->pdo) || $this->pdo === null) {
      $this->pdo = $this->getConnection();
    }
    return $this;
  }

  /**
   * @param array|string $table
   * @return \DataDriver\DataDriver
   */
  public function select($table)
  {
    $table = $this->sql->setAlias($table);
    $this->sql->select($table);

    return $this;
  }

  /**
   * @param array|string $table
   * @param mixed $values
   * @return \DataDriver\DataDriver
   */
  public function insert($table, ...$values): self
  {
    $table = $this->sql->setAlias($table);

    $this->sql->setValues($values);

    $this->sql->insert($table);
    return $this;
  }

  /**
   * @param array|string $table
   * @param mixed $values
   * @return \DataDriver\DataDriver
   */
  public function update($table, ...$values): self
  {
    $table = $this->sql->setAlias($table);

    $this->sql->setValues($values);

    $this->sql->update($table);
    return $this;
  }

  /**
   * @param array|string $table
   * @return \DataDriver\DataDriver
   */
  public function delete($table): self
  {
    $table = $this->sql->setAlias($table);

    $this->sql->delete($table);

    return $this;
  }

  /**
   * @param null|string $columns
   * @return \DataDriver\DataDriver
   */
  public function columns(?string $columns = null): self
  {
    $this->sql->columns($columns);
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
      $this->sql->call("ifHave", [$this, "prepareParam"]);

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
    try {
      $registerFunctions = RegisterFunctionsCollection::class;
      $datadriver = $this;
      foreach (get_class_methods($registerFunctions) as $funcName)
        Collection::macro($funcName, fn (...$value) => $datadriver->$funcName($this, ...$value));
    } catch (Exception $ex) {
      throw $ex;
    }
  }
}
