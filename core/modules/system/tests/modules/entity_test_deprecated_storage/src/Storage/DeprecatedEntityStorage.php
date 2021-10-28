<?php

namespace Drupal\entity_test_deprecated_storage\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Class for testing deprecation warnings from EntityStorageBase.
 *
 * @todo Remove this in Drupal 10.
 * @see https://www.drupal.org/project/drupal/issues/3244802
 */
class DeprecatedEntityStorage extends SqlContentEntityStorage {

  /**
   * Sets the entity class via deprecated means.
   *
   * @param string $class_name
   *   The name of the entity class to use.
   */
  public function setEntityClass(string $class_name): void {
    $this->entityClass = $class_name;
  }

  /**
   * Gets the current entity class via deprecated means.
   */
  public function getCurrentEntityClass(): string {
    return $this->entityClass;
  }

}
