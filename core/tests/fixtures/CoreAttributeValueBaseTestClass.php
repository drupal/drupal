<?php

namespace Drupal\Tests\Core\Template;

use Drupal\Core\Template\AttributeValueBase as CoreAttributeValueBase;

/**
 * A helper class for testing deprecation of Core\Template\AttributeValueBase.
 *
 * @todo remove this class in Drupal 10.
 *
 * @internal
 */
class CoreAttributeValueBaseTestClass extends CoreAttributeValueBase {

  /**
   * Implements the magic __toString() method.
   */
  public function __toString(): string {
    return '';
  }

}
