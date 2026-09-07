<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\DesignToken\Enum;

/**
 * Enumeration of the dimension token value possible units.
 */
enum DimensionUnit: string implements DimensionUnitInterface {

  case Rem = 'rem';
  case Px = 'px';

}
