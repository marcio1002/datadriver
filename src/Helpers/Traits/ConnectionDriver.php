<?php

namespace Datadriver\Helpers\Traits;

use PDO, PDOException, Exception;

trait ConnectionDriver
{
  protected function getConnection(): \PDO
  {
    try {
      return new PDO(
        DB_CONFIG["DRIVE"] . ":host=" . DB_CONFIG["DB_HOST"] . ";port=" . DB_CONFIG["DB_PORT"] . ";dbname=" . DB_CONFIG["DB_NAME"],
        DB_CONFIG["DB_USERNAME"],
        DB_CONFIG["DB_PASSWD"],
        DB_CONFIG["OPTIONS"]
      );
    } catch (Exception | PDOException $ex) {
      exit($ex->getMessage());
    }
  }
}
