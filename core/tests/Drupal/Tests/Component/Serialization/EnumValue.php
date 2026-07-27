<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Serialization;

/**
 * Represents options for a boolean-like value.
 */
enum EnumValue {
  case Yes;
  case No;
  case Maybe;
}
