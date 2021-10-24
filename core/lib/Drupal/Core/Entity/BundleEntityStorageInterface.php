<?php

namespace Drupal\Core\Entity;

/**
 * A storage that supports entities with bundle specific classes.
 */
interface BundleEntityStorageInterface {

  /**
   * Retrieves the bundle name for a provided class name.
   *
   * @param string $class_name
   *   The class name to check.
   *
   * @return string|null
   *   The bundle name of the class provided or NULL if unable to determine the
   *   bundle from the provided class.
   *
   * @throws \Drupal\Core\Entity\Exception\AmbiguousBundleClassException
   *   Thrown when multiple bundles are using the provided class.
   */
  public function getBundleFromClass(string $class_name): ?string;

}
