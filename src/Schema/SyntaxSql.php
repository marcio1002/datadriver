<?php

namespace Datadriver\Schema;

use Datadriver\Schema\{Mysql, Pgsql, Sqlite};

class SyntaxSql
{
  private array $syntax = [
    "instruct" => [
      "insert" => "INSERT INTO {table} ({columns}) VALUES({insertValues})",
      "select" => "SELECT {columns} FROM {table}",
      "update" => "UPDATE {table} SET {updateColumns}",
      "delete" => "DELETE FROM {table}"
    ],
    "mixed" => [
      "where" => "WHERE {args} ",
      "limit" =>  "LIMIT {args} OFFSET {args}",
      "in" => "WHERE {args} IN({values}) ",
      "notIn" => "WHERE {args} NOT IN({values})",
      "and" => "AND {args} ",
      "or" => "OR {args}",
      "order" => "ORDER BY {args} {args}",
      "having" => "HAVING {args} ",
      "join" => "INNER JOIN {args} ON {args}",
      "group" => "GROUP BY {args}"
    ],
    Mysql::class => [],
    Pgsql::class => [],
    Sqlite::class => []
  ];

  public function get(string $schema): ?array
  {
    return  $this->syntax["mixed"] + ($this->syntax[$schema] ?? [])  ?? null;
  }

  public function getInstruction($key): ?string
  {
    return $this->syntax["instruct"][$key] ?? null;
  }
}
