<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Serialization;

/**
 * Represents options for a boolean-like value.
 */
enum BackedEnumValue: string {
  case Yes = 'yes';
  case No = 'no';
  case Maybe = 'maybe';
}
