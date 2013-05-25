<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityAccessController.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Language\Language;
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
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, $langcode = Language::LANGCODE_DEFAULT, User $account = NULL) {

    // @todo Remove this once we can rely on $account.
    if (!$account) {
      $account = user_load($GLOBALS['user']->uid);
    }

    if (($access = $this->getCache($entity, $operation, $langcode, $account)) !== NULL) {
      // Cache hit, no work necessary.
      return $access;
    }

    // Invoke hook_entity_access(), hook results take precedence over overridden
    // implementations of EntityAccessController::checkAccess(). Entities
    // that have checks that need to be done before the hook is invoked should
    // do so by overridding this method.

    // We grant access to the entity if both of these conditions are met:
    // - No modules say to deny access.
    // - At least one module says to grant access.
    $access = module_invoke_all($entity->entityType() . '_access', $entity, $operation, $account, $langcode);

    if (in_array(FALSE, $access, TRUE)) {
      $return = FALSE;
    }
    elseif (in_array(TRUE, $access, TRUE)) {
      $return = TRUE;
    }
    else {
      // No result from hook, so entity checks are done.
      $return = (bool) $this->checkAccess($entity, $operation, $langcode, $account);
    }
    return $this->setCache($return, $entity, $operation, $langcode, $account);
  }

  /**
   * Performs access checks.
   *
   * This method is supposed to be overwritten by extending classes that
   * do their own custom access checking.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check 'create' access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'edit', 'create' or
   *   'delete'.
   * @param string $langcode
   *   The language code for which to check access.
   * @param \Drupal\user\Plugin\Core\Entity\User $account
   *   The user for which to check access.
   *
   * @return bool|null
   *   TRUE if access was granted, FALSE if access was denied and NULL if access
   *   could not be determined.
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, User $account) {
    return NULL;
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
   *   The language code for which to check access.
   * @param \Drupal\user\Plugin\Core\Entity\User $account
   *   The user for which to check access.
   *
   * @return bool|null
   *   TRUE if access was granted, FALSE if access was denied and NULL if there
   *   is no record for the given user, operation, langcode and entity in the
   *   cache.
   */
  protected function getCache(EntityInterface $entity, $operation, $langcode, User $account) {
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
   *   The language code for which to check access.
   * @param \Drupal\user\Plugin\Core\Entity\User $account
   *   The user for which to check access.
   *
   * @return bool
   *   TRUE if access was granted, FALSE otherwise.
   */
  protected function setCache($access, EntityInterface $entity, $operation, $langcode, User $account) {
    $uid = $account ? $account->id() : 0;
    $uuid = $entity->uuid();

    // Save the given value in the static cache and directly return it.
    return $this->accessCache[$uid][$uuid][$langcode][$operation] = (bool) $access;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $this->accessCache = array();
  }

}
