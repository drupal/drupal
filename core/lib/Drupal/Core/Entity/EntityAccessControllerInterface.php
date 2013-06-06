<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityAccessControllerInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Language\Language;
// @todo Don't depend on module level code.
use Drupal\user\UserInterface;

/**
 * Defines a common interface for entity access controller classes.
 */
interface EntityAccessControllerInterface {

  /**
   * Checks access to an operation on a given entity or entity translation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view", "create", "update" or "delete".
   * @param string $langcode
   *   (optional) The language code for which to check access. Defaults to
   *   Language::LANGCODE_DEFAULT.
   * @param \Drupal\user\UserInterface $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   *
   * @return bool
   *   TRUE if access was granted, FALSE otherwise.
   */
  public function access(EntityInterface $entity, $operation, $langcode = Language::LANGUAGE_DEFAULT, UserInterface $account = NULL);

  /**
   * Clears all cached access checks.
   */
  public function resetCache();

}
