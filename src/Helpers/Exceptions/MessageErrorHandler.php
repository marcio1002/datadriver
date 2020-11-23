<?php

namespace Datadriver\Helpers\Exceptions;

use Datadriver\Helpers\Traits\{DataImplements, DomHelper};
use DOMDocument, DOMElement;

trait MessageErrorHandler
{
  use DomHelper, DataImplements;

  private static ?DOMDocument $doc = null;
  private static ?DOMElement $head = null;
  private static ?DOMElement $body = null;
  private static ?DOMElement $divMain = null;
  private static $c = 0;
  private static $joinArr = "";

  protected function toString(string $glue, array $arr): ?string
  {
    if (static::$c === 0) static::$joinArr = "";
    foreach ($arr as $item) {
      static::$c += 1;
      if (is_array($item)) $this->toString($glue, $item);
      if (is_object($item)) $item = get_class($item);
      if (!is_array($item)) static::$joinArr .= $glue . $item;
    }
    static::$c = 0;
    return static::$joinArr ?? null;
  }

  protected function createHead(): self
  {
    static::$doc = new DOMDocument("5.2", "utf-8");

    $html = static::$doc->createElement("html");
    $html = static::$doc->appendChild($html);

    static::$head = static::$doc->createElement("head");
    static::$head = $html->appendChild(static::$head);

    static::$head->appendChild(static::$doc->createElement("title", "Error exception thrown"));

    $this->createElementMeta(["charset" => "UTF-8"]);
    $this->createElementMeta(["name" => "viewport", "content" => "width=device-width, initial-scale=1.0"]);


    $this->createElementLink("preconnect", "https://fonts.gstatic.com");
    $this->createElementLink("stylesheet", "https://fonts.googleapis.com/css2?family=Noto+Sans+JP&display=swap");

    $this->createElemetStyle("body{ font-family:'Roboto', sans-serif; border:0; padding:0; background-color:#EBEBEB;} .error_content{ max-height:100vh; max-width:100vw; width:97%; height:auto; display:flex; flex-direction:column; justify-content:center; align-items:between; flex-wrap:wrap; margin:0 auto; overflow:hidden;} .error_header{ padding-top:.80em; padding-bottom:.80em;} span{ background-color:#FF5734; padding:.2em; line-height:1.6;} th,table,p{ flex:1 0 0%;} h1{ text-align:center; font-size:1.4rem;} .table{ border-collapse:collapse; width:100%; margin-bottom:1rem; background-color:#242B32; color:#f5f5f5; border:1px solid #C3C3C3; border-top-left-radius:.40rem; border-top-right-radius:.40rem;} .table td, .table th{ padding:.75rem; vertical-align:top;} .table tbody tr:nth-of-type(odd){ background-color:rgba(217,217,217, 0.2);} .table th{ text-align:center; color:#9E77EB;} .table td{ border-top:2px solid #9E77EB; font-weight:500; color:#f5f5f5;}");
    return $this;
  }

  protected function setErrorDescription(...$errorInfo): self
  {
    $div = static::$divMain->appendChild(static::$doc->createElement("div"));
    $div->appendChild($this->createAttrClass("error_header"));
    $title = $div->appendChild(static::$doc->createElement("h1", htmlspecialchars($errorInfo[0])));

    $title->appendChild($this->createAttrClass("title"));

    $errDesc = [
      "message" => htmlspecialchars($errorInfo[0]),
      "line" => htmlspecialchars($errorInfo[1]),
      "file" => htmlspecialchars($errorInfo[2]),
      "code" => htmlspecialchars($errorInfo[3]),
    ];

    foreach ($errDesc as  $k => $errDesc) {
      $pDesc = static::$doc->createElement("p");
      $pDesc->appendChild(static::$doc->createElement("strong", ucwords($k) . ": "));
      $pDesc->appendChild(static::$doc->createElement("span", $errDesc));
      $pDesc->appendChild($this->createAttrClass("error_description"));
      $div->appendChild($pDesc);
    }
    return $this;
  }

  protected function createBody(): self
  {
    static::$body =  static::$doc->createElement("body");
    static::$body = static::$doc->appendChild(static::$body);

    static::$divMain = static::$doc->createElement("div");
    static::$body->appendChild(static::$divMain);
    static::$divMain->appendChild($this->createAttrClass("error_content"));

    return $this;
  }

  protected function createTable(array $errorInfo): self
  {
    $table = static::$doc->createElement("table");
    $table->appendChild($this->createAttrClass("table"));
    static::$divMain->appendChild($table);

    $tHead = static::$doc->createElement("thead");
    $tHead = $table->appendChild($tHead);

    $tBody = static::$doc->createElement("tbody");
    $tBody = $table->appendChild($tBody);

    $thIndex = static::$doc->createElement("th");
    $thIndex->appendChild(static::$doc->createTextNode("#"));
    $tHead->appendChild($thIndex);

    if (count($errorInfo) > 0) {

      foreach ($errorInfo as $in => $trace) {
        if ($in == 0) {
          foreach (array_keys($trace) as $keys)
            $tHead->appendChild(static::$doc->createElement("th", htmlspecialchars($keys)));
        }

        $tr = static::$doc->createElement("tr");
        $tr->appendChild(static::$doc->createElement("td", $in));
        $tBody->appendChild($tr);
        foreach ($trace as $errDesc) {
          $td = static::$doc->createElement("td", is_array($errDesc) ? $this->toString(" ", $errDesc) : $errDesc);
          $tr->appendChild($td);
          $tBody->appendChild($tr);
        }
      }
    }

    return $this;
  }

  protected function render()
  {
    return static::$doc->saveHTML();
  }

  public function sendMessage()
  {
  }
}
