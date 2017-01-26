<?php

namespace Drupal\field_layout\Entity;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field_layout\Display\EntityDisplayWithLayoutInterface;

/**
 * Provides an entity form display entity that has a layout.
 */
class FieldLayoutEntityFormDisplay extends EntityFormDisplay implements EntityDisplayWithLayoutInterface {

  use FieldLayoutEntityDisplayTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultRegion() {
    // This cannot be provided by the trait due to
    // https://bugs.php.net/bug.php?id=71414 which is fixed in PHP 7.0.6.
    return $this->getLayoutDefinition($this->getLayoutId())->getDefaultRegion();
  }

}
