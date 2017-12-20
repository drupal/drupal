<?php

namespace Drupal\Core\Entity;

/**
 * A storage that supports translatable entity types.
 */
interface TranslatableStorageInterface {

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
   *   Another instance of the specified entity object class with the specified
   *   active language and initial values.
   *
   * @todo Consider accepting \Drupal\Core\Entity\TranslatableInterface as first
   *   parameter. See https://www.drupal.org/project/drupal/issues/2932049.
   */
  public function createTranslation(ContentEntityInterface $entity, $langcode, array $values = []);

}
