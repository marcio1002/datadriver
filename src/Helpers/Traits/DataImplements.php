<?php

namespace Datadriver\Helpers\Traits;

use Illuminate\Support\Collection;

trait DataImplements
{
  /**
   * @param object $object
   *  The object to be converted
   * @return \Illuminate\Support\Collection
   */
  public function toArray(object $object): array
  {
    $classAnonymous = new class{};

    $classAnonymous->object = $object;
    $array = [];

    foreach ($classAnonymous->object as $property => $val) $array[$property] = $val;

    return $array;
  }

  public function setCollection(array $mixed): void
  {
    if(!(static::$data instanceof Collection)) static::$data = collect([]);

    foreach ($mixed as  $val) {
      if (is_object($val)) static::$data->add($this->toArray($val));
      if (is_array($val)) static::$data->add($val);
      static::$data->add($val);
    }
  }
}
