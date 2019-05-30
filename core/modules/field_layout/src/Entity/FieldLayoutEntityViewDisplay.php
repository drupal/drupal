<?php

namespace Drupal\field_layout\Entity;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field_layout\Display\EntityDisplayWithLayoutInterface;

/**
 * Provides an entity view display entity that has a layout.
 */
class FieldLayoutEntityViewDisplay extends EntityViewDisplay implements EntityDisplayWithLayoutInterface {

  use FieldLayoutEntityDisplayTrait;

}
