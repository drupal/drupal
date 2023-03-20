<?php

namespace Drupal\Core\Entity;

use Drupal\Core\TypedData\TranslatableInterface as TranslatableDataInterface;

/**
 * Provides methods for an entity to support translation.
 *
 * @ingroup entity_type_characteristics
 */
interface TranslatableInterface extends TranslatableDataInterface, EntityInterface {

  /**
   * Determines if the current translation of the entity has unsaved changes.
   *
   * @return bool
   *   TRUE if the current translation of the entity has changes.
   */
  public function hasTranslationChanges();

}
