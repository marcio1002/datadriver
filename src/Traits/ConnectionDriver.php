<?php

namespace Datadriver\Traits;

use PDO, PDOException, Exception;

trait ConnectionDriver
{
  protected function getConnection(): \PDO
  {
    try {
      if((strtolower(DB_CONFIG["DRIVE"] ) <=> "sqlite") === 0) 
        return new PDO("sqlite:" . DB_CONFIG["SQLITE_PATH"]);
      else
        return new PDO(
          DB_CONFIG["DRIVE"] . 
          ":host=" . DB_CONFIG["DB_HOST"] . 
          ";port=" . DB_CONFIG["DB_PORT"] . 
          ";dbname=" . DB_CONFIG["DB_NAME"],
          DB_CONFIG["DB_USERNAME"],
          DB_CONFIG["DB_PASSWD"],
          DB_CONFIG["OPTIONS"]
        );
    } catch (Exception | PDOException $ex) {
      throw $ex;
    }
  }
}
