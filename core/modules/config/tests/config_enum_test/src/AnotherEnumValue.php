<?php

declare(strict_types=1);

namespace Drupal\config_enum_test;

/**
 * A test enum.
 *
 * Used by config_enum_dependency_test.
 */
enum AnotherEnumValue {
  case Foo;
  case Bar;
}
