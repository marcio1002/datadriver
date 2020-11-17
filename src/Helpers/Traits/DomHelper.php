<?php

namespace Datadriver\Helpers\Traits;

use DOMAttr;

trait DomHelper
{

  protected function createElementLink(string $type, string $url): void
  {
    $link = static::$doc->createElement("link");
    static::$head->appendChild($link);

    $rel = static::$doc->createAttribute("rel");
    $rel->value = $type;

    $href = static::$doc->createAttribute("href");
    $href->value = htmlspecialchars($url);

    $link->appendChild($rel);
    $link->appendChild($href);
  }

  protected function createElementMeta(array $atributes): void
  {
    $meta = static::$doc->createElement("meta");
    $meta = static::$head->appendChild($meta);

    foreach ($atributes as $atribute => $value) {
      $atribute = static::$doc->createAttribute($atribute);
      $atribute->value = $value;
      $meta->appendChild($atribute);
    }
  }

  protected function createAttrClass(string $class): DOMAttr
  {
    $classAttr = static::$doc->createAttribute("class");
    $classAttr->value = $class;

    return $classAttr;
  }

  protected function createAttrId(string $id): DOMAttr
  {
    $idAttr = static::$doc->createAttribute("id");
    $idAttr->value = $id;

    return $idAttr;
  }

  protected function createElemetStyle(string $stylesAttr)
  {
    $style = static::$doc->createElement("style", $stylesAttr);
    $style = static::$head->appendChild($style);
    $type = static::$doc->createAttribute("type");
    $type->value = "text/css";
    $style->appendChild($type);
  }
}
