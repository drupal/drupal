<?php

namespace Drupal\field_layout\Entity;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field_layout\Display\EntityDisplayWithLayoutInterface;

/**
 * Provides an entity form display entity that has a layout.
 */
class FieldLayoutEntityFormDisplay extends EntityFormDisplay implements EntityDisplayWithLayoutInterface {

  use FieldLayoutEntityDisplayTrait;

}
