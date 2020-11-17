<?php

namespace Datadriver\Helpers\Traits;

use Closure;
use Illuminate\Support\Collection;
use Exception;

trait RegisterFunctionsCollection
{
  /**
   * @param Collection $collection
   * @param array|callable $function
   * @return Collection
   */
  public function ifEmpty(Collection $collection, $callback): Collection
  {
    if ($collection->isEmpty()) {
      if (is_array($callback)) {
        [$class, $method] = $callback;
        $class->$method($collection);
      } else {
        if(!is_callable($callback)) throw new Exception("{$callback} is not a function");
        if($callback instanceof Closure) $callback = $callback->bindTo($collection);
        
        $callback($collection);
      }
    }
    return $collection;
  }

  /**
   * @param Collection $collection
   * @param string|array|callable $function
   * @return Collection
   */
  public function ifHave(Collection $collection, $callback): Collection
  {
    if ($collection->isNotEmpty()) {
      if (is_array($callback)) {
        [$class, $method] = $callback;
        $class->$method($collection);
      } else {
        if(!is_callable($callback)) throw new Exception("{$callback} is not a function");
        if($callback instanceof Closure) $callback = $callback->bindTo($collection);
        
        $callback($collection);
      }
    }
    return $collection;
  }

  /**
   * @param Collection $collection
   * @return object
   */
  public function toObject(Collection $collection): Object
  { 
    $object = new class{ };
    $collection->each(fn($item, $in) => ($object->{$in} = $item));
    return $object;
  }
}
