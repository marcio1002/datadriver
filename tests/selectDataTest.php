<?php
require_once dirname(__DIR__) . "/vendor/autoload.php";

use Datadriver\DataDriver;



(new DataDriver)
  ->select("empresa")
  ->columns("id_empresa,fantasia,email,cnpj")
  ->orderBy("id_empresa")
  ->fetch("response");


  function response($vals)
  {
    $html = "<!DOCTYPE html><html><head><style>table{ font-family:arial, sans-serif; border-collapse:collapse; width:100%}td, th{ border:1px solid #dddddd; text-align:left; padding:8px}tr:nth-child(even){ background-color:#dddddd}</style></head><body><h2>HTML Table</h2>{{TABLE}}</body></html>" ;
    $header = "";
    $body = "";
    $table = "<table><tr>{{HEADER}}</tr>{{BODY}}</table>";
    foreach($vals as $key => $val) {
      $body .="<tr>"; 
      if(is_array($val)) {
        foreach(array_keys($vals) as $k) $header = "<td>{$k}</td>";
        foreach($val as $v) $body .= "<td>{$v}</td>";
      }else {
        $header .= "<td>{$key}</td>";
        $body .= "<td>{$val}</td>";
      }
      $body .="</tr>"; 
    }
    $table = preg_replace("/{{HEADER}}/",$header,$table);
    $table = preg_replace("/{{BODY}}/",$body,$table);
    $html = preg_replace("/{{TABLE}}/",$table,$html);
    echo $html;
  }