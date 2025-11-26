<?php

namespace Drupal\Component\Utility;

/**
 * Provides helper methods for reflection.
 */
final class Reflection {

  /**
   * Gets the parameter's class name.
   *
   * @param \ReflectionParameter $parameter
   *   The parameter.
   *
   * @return string|null
   *   The parameter's class name or NULL if the parameter is not a class.
   */
  public static function getParameterClassName(\ReflectionParameter $parameter) : ?string {
    $name = NULL;
    $parameterType = $parameter->getType();
    if ($parameterType instanceof \ReflectionNamedType && !$parameterType->isBuiltin()) {
      $name = $parameterType->getName();
      $lc_name = strtolower($name);
      switch ($lc_name) {
        case 'self':
          return $parameter->getDeclaringClass()->getName();

        case 'parent':
          return ($parent = $parameter->getDeclaringClass()->getParentClass()) ? $parent->name : NULL;
      }
    }
    return $name;
  }

}
