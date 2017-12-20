<?php

namespace Drupal\Core\Entity;

use Drupal\Core\TypedData\TranslatableInterface as TranslatableDataInterface;

/**
 * Provides methods for an entity to support translation.
 */
interface TranslatableInterface extends TranslatableDataInterface {

  /**
   * Determines if the current translation of the entity has unsaved changes.
   *
   * @return bool
   *   TRUE if the current translation of the entity has changes.
   */
  public function hasTranslationChanges();

}
