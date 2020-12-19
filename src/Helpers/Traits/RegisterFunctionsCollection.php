<?php

namespace Datadriver\Helpers\Traits;

use Closure, Exception, Illuminate\Support\Collection;

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

        if (method_exists($class, $method))  $class->$method($collection);
      } else {
        if (!is_callable($callback)) throw new Exception("{$callback} is not a function");
        if ($callback instanceof Closure) $callback = $callback->bindTo($collection);

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

        if (method_exists($class, $method))  $class->$method($collection);
      } else {
        if (!is_callable($callback)) throw new Exception("{$callback} is not a function");
        if ($callback instanceof Closure) $callback = $callback->bindTo($collection);

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
    $object = new class{};
    
    $collection->each(fn ($item, $in) => ($object->{$in} = $item));
    return $object;
  }

  /**
   * @param Collection $collection
   * @param string $key
   * @param mixed $value
   * @param bool $recursive
   * @return object
   */
  public function ppendKey(Collection $collection, string $key, $value, bool $recursive = false): Collection
  {
    $items = $collection->toArray();

    if (is_null($items) && $recursive)
      $items = $value;

    if ($recursive && is_array($items))
      foreach ($items as $k => $it) {
        if (isset($items[$k][$key])) {
          if (!is_array($items[$k][$key])) $items[$k][$key] = [$it[$key]];
          array_unshift($items[$k][$key], $value);
        }
      }
    else {
      if (!is_array($items[$key])) $items[$key] = [$items[$key]];
      array_unshift($items[$key], $value);
    }

    return collect($items);
  }

  /**
   * @param Collection $collection
   * @param string $key
   * @param mixed $value
   * @param bool $recursive
   * @return object
   */
  public function set(Collection $collection, string $key, $value, bool $recursive = false): Collection
  {
    if (is_null($collection->get($key)))
      $collection->put($key, "");

    foreach ($collection->toArray() as $k => $v) {
      if (($k <=> $key) == 0) 
        if (!$recursive) {
          if (is_array($v))
            array_push($v, $value);
          else
           $v = (empty($v)) ?  $value : [$v, $value];

          $items = $v;
        }else 
          $items[] = [$key => $value];
    }
    $collection->put($key, $items);

    return $collection;
  }
}
