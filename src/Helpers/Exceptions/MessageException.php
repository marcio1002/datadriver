<?php

namespace Datadriver\Helpers\Exceptions;

class MessageException
{
  use MessageErrorHandler;
  private static $ex;

  public function __construct($ex)
  {
    static::$ex = $ex;
  }

  public function sendMessage()
  {
    echo $this
      ->createHead()
      ->createBody()
      ->setErrorDescription(
        static::$ex->getMessage(),
        static::$ex->getLine(),
        static::$ex->getFile(),
        static::$ex->getCode()
      )
      ->createTable(static::$ex->getTrace())
      ->render();
  }
}
