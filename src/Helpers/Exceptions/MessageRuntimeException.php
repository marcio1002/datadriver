<?php

namespace Datadriver\Helpers\Exceptions;

class MessageRuntimeException {

  use MessageErrorHandler;

  private static $ex;

  public function __construct($ex)
  {
    static::$ex = $ex[0];
  }

  public function sendMessage()
  {
    echo $this
      ->createHead()
      ->createBody()
      ->setErrorDescription(static::$ex[1],static::$ex[3],static::$ex[2],static::$ex[0])
      ->render();
  }
}