<?php

namespace Datadriver\Traits;

trait DataImplements
{

  /**
   * @param array $arr
   * An associative array with key / value or single array with key / value
   * @return object
   */
  public function toObject(array $arr): object
  {
    $object = new class{};

    foreach ($arr as $property => $val) {
      if (is_array($val)) $this->toObject($val);

      $object->{$property} = $val;
    }
    return $object;
  }

  /**
   * @param object $object
   *  The object to be converted
   * @return array
   */
  public function toArray(object $object): array
  {
    $classAnonymous = new class{};

    $classAnonymous->object = $object;
    $array = [];

    foreach ($classAnonymous->object as $property => $val) $array[$property] = $val;

    return $array;
  }
}
