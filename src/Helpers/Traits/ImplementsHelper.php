<?php

namespace Datadriver\Helpers\Traits;


trait ImplementsHelper
{

  /**
   * @param object $object
   *  The object to be converted
   * @return \Illuminate\Support\Collection
   */
  public function toArray(object $object): array
  {
    $classAnonymous = new class
    {
    };

    $classAnonymous->object = $object;
    $array = [];

    foreach ($classAnonymous->object as $property => $val) $array[$property] = $val;

    return $array;
  }
  
}
