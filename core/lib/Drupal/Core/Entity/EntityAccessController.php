<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityAccessController.
 */

namespace Drupal\Core\Entity;

use Drupal\user\Plugin\Core\Entity\User;

/**
 * Defines a default implementation for entity access controllers.
 */
class EntityAccessController implements EntityAccessControllerInterface {

  /**
   * Stores calculcated access check results.
   *
   * @var array
   */
  protected $accessCache = array();

  /**
   * Implements \Drupal\Core\Entity\EntityAccessControllerInterface::viewAccess().
   */
  public function viewAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    if (($access = $this->getCache($entity, 'view', $langcode, $account)) !== NULL) {
      return $access;
    }

    $access = (bool) $this->access($entity, 'view', $langcode, $account);
    return $this->setCache($access, $entity, 'view', $langcode, $account);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityAccessControllerInterface::createAccess().
   */
  public function createAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    if (($access = $this->getCache($entity, 'create', $langcode, $account)) !== NULL) {
      return $access;
    }

    $access = (bool) $this->access($entity, 'create', $langcode, $account);
    return $this->setCache($access, $entity, 'create', $langcode, $account);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityAccessControllerInterface::updateAccess().
   */
  public function updateAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    if (($access = $this->getCache($entity, 'update', $langcode, $account)) !== NULL) {
      return $access;
    }

    $access = (bool) $this->access($entity, 'update', $langcode, $account);
    return $this->setCache($access, $entity, 'update', $langcode, $account);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityAccessControllerInterface::deleteAccess().
   */
  public function deleteAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    if (($access = $this->getCache($entity, 'delete', $langcode, $account)) !== NULL) {
      return $access;
    }

    $access = (bool) $this->access($entity, 'delete', $langcode, $account);
    return $this->setCache($access, $entity, 'delete', $langcode, $account);
  }

  /**
   * Performs default, shared access checks.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check 'create' access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'edit', 'create' or
   *   'delete'.
   * @param string $langcode
   *   (optional) The language code for which to check access. Defaults to
   *   LANGUAGE_DEFAULT.
   * @param \Drupal\user\Plugin\Core\Entity\User $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   *
   * @return bool|null
   *   TRUE if access was granted, FALSE if access was denied and NULL if access
   *   could not be determined.
   */
  protected function access(EntityInterface $entity, $operation, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    // @todo Remove this once we can rely on $account.
    if (!$account) {
      $account = user_load($GLOBALS['user']->uid);
    }

    // We grant access to the entity if both of these conditions are met:
    // - No modules say to deny access.
    // - At least one module says to grant access.
    $access = module_invoke_all($entity->entityType() . '_access', $entity, $operation, $account, $langcode);
    if (in_array(FALSE, $access, TRUE)) {
      return FALSE;
    }
    elseif (in_array(TRUE, $access, TRUE)) {
      return TRUE;
    }
  }

  /**
   * Tries to retrieve a previously cached access value from the static cache.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check 'create' access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'edit', 'create' or
   *   'delete'.
   * @param string $langcode
   *   (optional) The language code for which to check access. Defaults to
   *   LANGUAGE_DEFAULT.
   * @param \Drupal\user\Plugin\Core\Entity\User $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   *
   * @return bool|null
   *   TRUE if access was granted, FALSE if access was denied and NULL if there
   *   is no record for the given user, operation, langcode and entity in the
   *   cache.
   */
  protected function getCache(EntityInterface $entity, $operation, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    // @todo Remove this once we can rely on $account.
    if (!$account) {
      $account = user_load($GLOBALS['user']->uid);
    }

    $uid = $account ? $account->id() : 0;
    $uuid = $entity->uuid();

    // Return from cache if a value has been set for it previously.
    if (isset($this->accessCache[$uid][$uuid][$langcode][$operation])) {
      return $this->accessCache[$uid][$uuid][$langcode][$operation];
    }
  }

  /**
   * Statically caches whether the given user has access.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check 'create' access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'edit', 'create' or
   *   'delete'.
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
  protected function setCache($access, EntityInterface $entity, $operation, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    // @todo Remove this once we can rely on $account.
    if (!$account) {
      $account = user_load($GLOBALS['user']->uid);
    }

    $uid = $account ? $account->id() : 0;
    $uuid = $entity->uuid();

    // Save the given value in the static cache and directly return it.
    return $this->accessCache[$uid][$uuid][$langcode][$operation] = (bool) $access;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityAccessControllerInterface::clearCache().
   */
  public function resetCache() {
    $this->accessCache = array();
  }

}
