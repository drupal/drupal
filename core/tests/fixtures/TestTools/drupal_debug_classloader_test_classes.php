<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace Drupal\drupal_debug_test_core;

/**
 * Fixture: parent class with @return annotations but no native return types.
 */
class ParentWithReturn {

  /**
   * @return string
   *   A test string.
   */
  public function testMethod() {
    return 'test';
  }

  /**
   * @return int
   *   A test integer.
   */
  public function anotherMethod() {
    return 42;
  }

}

/**
 * Fixture: child in the same module as the parent.
 */
class SameModuleChild extends ParentWithReturn {

  /**
   * {@inheritdoc}
   */
  public function testMethod() {
    return 'same module';
  }

}

namespace Drupal\drupal_debug_test_other;

use Drupal\drupal_debug_test_core\ParentWithReturn;

/**
 * Fixture: cross-module child without native return type.
 */
class ChildWithoutReturnType extends ParentWithReturn {

  /**
   * {@inheritdoc}
   */
  public function testMethod() {
    return 'overridden';
  }

}

/**
 * Fixture: cross-module child with native return type.
 */
class ChildWithNativeReturnType extends ParentWithReturn {

  /**
   * {@inheritdoc}
   */
  public function testMethod(): string {
    return 'overridden';
  }

}

/**
 * Fixture: cross-module child with own @return annotation.
 */
class ChildWithReturnAnnotation extends ParentWithReturn {

  /**
   * @return string
   *   A test string.
   */
  public function testMethod() {
    return 'overridden';
  }

}

/**
 * Fixture: cross-module child with deprecated method.
 */
class ChildWithDeprecatedMethod extends ParentWithReturn {

  /**
   * @deprecated in drupal:11.0.0 and is removed from drupal:12.0.0.
   *   Use something else instead.
   * @see https://www.drupal.org/node/9999999
   */
  public function testMethod() {
    return 'overridden';
  }

}

/**
 * Fixture: cross-module child that does not override the method.
 */
class ChildWithoutOverride extends ParentWithReturn {

}
