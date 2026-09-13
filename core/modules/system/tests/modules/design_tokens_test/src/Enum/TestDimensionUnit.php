<?php

declare(strict_types=1);

namespace Drupal\design_tokens_test\Enum;

use Drupal\Core\Theme\DesignToken\Enum\DimensionUnitInterface;

/**
 * Allows to test with another implementation of DimensionUnitInterface.
 */
enum TestDimensionUnit: string implements DimensionUnitInterface {

  case Rem = 'rem';
  case Px = 'px';
  case Em = 'em';

}
