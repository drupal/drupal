<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityAccessControllerInterface.
 */

namespace Drupal\Core\Entity;

// @todo Don't depend on module level code.
use Drupal\user\Plugin\Core\Entity\User;

/**
 * Defines a common interface for entity access controller classes.
 */
interface EntityAccessControllerInterface {

  /**
   * Checks 'view' access for a given entity or entity translation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check 'view' access.
   * @param string $langcode
   *   (optional) The language code for which to check access. Defaults to
   *   LANGUAGE_DEFAULT.
   * @param \Drupal\user\Plugin\Core\Entity\User $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   *
   * @return bool
   *   TRUE if access was granted, FALSE otherwise.
   */
  public function viewAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL);

  /**
   * Checks 'create' access for a given entity or entity translation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check 'create' access.
   * @param string $langcode
   *   (optional) The language code for which to check access. Defaults to
   *   LANGUAGE_DEFAULT.
   * @param \Drupal\user\Plugin\Core\Entity\User $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   *
   * @return bool
   *   TRUE if access was granted, FALSE otherwise.
   */
  public function createAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL);

  /**
   * Checks 'update' access for a given entity or entity translation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check 'update' access.
   * @param string $langcode
   *   (optional) The language code for which to check access. Defaults to
   *   LANGUAGE_DEFAULT.
   * @param \Drupal\user\Plugin\Core\Entity\User $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   *
   * @return bool
   *   TRUE if access was granted, FALSE otherwise.
   */
  public function updateAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL);

  /**
   * Checks 'delete' access for a given entity or entity translation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check 'delete' access.
   * @param string $langcode
   *   (optional) The language code for which to check access. Defaults to
   *   LANGUAGE_DEFAULT.
   * @param \Drupal\user\Plugin\Core\Entity\User $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   *
   * @return bool
   *   TRUE if access was granted, FALSE otherwise.
   */
  public function deleteAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL);
}
