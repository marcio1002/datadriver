<?php

namespace Datadriver\Exceptions;

class MessageError {

  use MessageErrorHandler;

  private static $ex;

  public function __construct($ex)
  {
    static::$ex = $ex;
    $this->sendMessage();
  }

  private function sendMessage()
  {
    echo $this->send(static::$ex[1],static::$ex[3],static::$ex[2],static::$ex[0]);
  }
}