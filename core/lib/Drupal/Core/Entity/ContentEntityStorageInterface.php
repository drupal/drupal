<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityStorageInterface.
 */

namespace Drupal\Core\Entity;

/**
 * A storage that supports content entity types.
 */
interface ContentEntityStorageInterface extends DynamicallyFieldableEntityStorageInterface {

  /**
   * Constructs a new entity translation object, without permanently saving it.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object being translated.
   * @param string $langcode
   *   The translation language code.
   * @param array $values
   *   (optional) An associative array of initial field values keyed by field
   *   name. If none is provided default values will be applied.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   A new entity translation object.
   */
  public function createTranslation(ContentEntityInterface $entity, $langcode, array $values = []);

}
