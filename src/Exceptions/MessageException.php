<?php

namespace Datadriver\Exceptions;

class MessageException
{
  use MessageErrorHandler;
  private static $ex;

  public function __construct($ex)
  {
    static::$ex = $ex;
    $this->sendMessage();
  }

  private function sendMessage()
  {
    echo $this->send(
        static::$ex->getMessage(),
        static::$ex->getLine(),
        static::$ex->getFile(),
        static::$ex->getCode()
    );
  }
}
