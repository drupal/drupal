<?php

namespace Drupal\Tests\ckeditor5\Traits;

/**
 * Provides methods to test protected/private methods in unit tests.
 *
 * @internal
 */
trait PrivateMethodUnitTestTrait {

  /**
   * Gets a protected/private method to test.
   *
   * @param string $fqcn
   *   A fully qualified classname.
   * @param string $name
   *   The method name.
   *
   * @return \ReflectionMethod
   *   The accessible method.
   */
  protected static function getMethod(string $fqcn, string $name): \ReflectionMethod {
    $class = new \ReflectionClass($fqcn);
    $method = $class->getMethod($name);
    return $method;
  }

}
