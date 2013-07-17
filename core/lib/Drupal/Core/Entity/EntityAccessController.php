<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityAccessController.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;

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
   * The entity type of the access controller instance.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Constructs an access controller instance.
   *
   * @param string $entity_type
   *   The entity type of the access controller instance.
   */
  public function __construct($entity_type) {
    $this->entity_type = $entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, $langcode = Language::LANGCODE_DEFAULT, AccountInterface $account = NULL) {
    $account = $this->prepareUser($account);

    if (($access = $this->getCache($entity->uuid(), $operation, $langcode, $account)) !== NULL) {
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
    $access = module_invoke_all($entity->entityType() . '_access', $entity->getBCEntity(), $operation, $account, $langcode);

    if (($return = $this->processAccessHookResults($access)) === NULL) {
      // No module had an opinion about the access, so let's the access
      // controller check create access.
      $return = (bool) $this->checkAccess($entity, $operation, $langcode, $account);
    }
    return $this->setCache($return, $entity->uuid(), $operation, $langcode, $account);
  }

  /**
   * We grant access to the entity if both of these conditions are met:
   * - No modules say to deny access.
   * - At least one module says to grant access.
   *
   * @param array $access
   *   An array of access results of the fired access hook.
   *
   * @return bool|NULL
   *   Returns FALSE if access should be denied, TRUE if access should be
   *   granted and NULL if no module denied access.
   */
  protected function processAccessHookResults(array $access) {
    if (in_array(FALSE, $access, TRUE)) {
      return FALSE;
    }
    elseif (in_array(TRUE, $access, TRUE)) {
      return TRUE;
    }
    else {
      return;
    }
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
   *   The entity operation. Usually one of 'view', 'update', 'create' or
   *   'delete'.
   * @param string $langcode
   *   The language code for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return bool|null
   *   TRUE if access was granted, FALSE if access was denied and NULL if access
   *   could not be determined.
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    return NULL;
  }

  /**
   * Tries to retrieve a previously cached access value from the static cache.
   *
   * @param string $cid
   *   Unique string identifier for the entity/operation, for example the
   *   entity UUID or a custom string.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'update', 'create' or
   *   'delete'.
   * @param string $langcode
   *   The language code for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return bool|null
   *   TRUE if access was granted, FALSE if access was denied and NULL if there
   *   is no record for the given user, operation, langcode and entity in the
   *   cache.
   */
  protected function getCache($cid, $operation, $langcode, AccountInterface $account) {
    // Return from cache if a value has been set for it previously.
    if (isset($this->accessCache[$account->id()][$cid][$langcode][$operation])) {
      return $this->accessCache[$account->id()][$cid][$langcode][$operation];
    }
  }

  /**
   * Statically caches whether the given user has access.
   *
   * @param bool $access
   *   TRUE if the user has access, FALSE otherwise.
   * @param string $cid
   *   Unique string identifier for the entity/operation, for example the
   *   entity UUID or a custom string.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'update', 'create' or
   *   'delete'.
   * @param string $langcode
   *   The language code for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return bool
   *   TRUE if access was granted, FALSE otherwise.
   */
  protected function setCache($access, $cid, $operation, $langcode, AccountInterface $account) {
    // Save the given value in the static cache and directly return it.
    return $this->accessCache[$account->id()][$cid][$langcode][$operation] = (bool) $access;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $this->accessCache = array();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = array()) {
    $account = $this->prepareUser($account);
    $context += array(
      'langcode' => Language::LANGCODE_DEFAULT,
    );

    $cid = $entity_bundle ? 'create:' . $entity_bundle : 'create';
    if (($access = $this->getCache($cid, 'create', $context['langcode'], $account)) !== NULL) {
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
    $access = module_invoke_all($this->entity_type . '_create_access', $account, $context['langcode']);

    if (($return = $this->processAccessHookResults($access)) === NULL) {
      // No module had an opinion about the access, so let's the access
      // controller check create access.
      $return = (bool) $this->checkCreateAccess($account, $context, $entity_bundle);
    }
    return $this->setCache($return, $cid, 'create', $context['langcode'], $account);
  }

  /**
   * Performs create access checks.
   *
   * This method is supposed to be overwritten by extending classes that
   * do their own custom access checking.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   * @param array $context
   *   An array of key-value pairs to pass additional context when needed.
   * @param string|null $entity_bundle
   *   (optional) The bundle of the entity. Required if the entity supports
   *   bundles, defaults to NULL otherwise.
   *
   * @return bool|null
   *   TRUE if access was granted, FALSE if access was denied and NULL if access
   *   could not be determined.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return NULL;
  }

  /**
   * Loads the current account object, if it does not exist yet.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account interface instance.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   Returns the current account object.
   */
  protected function prepareUser(AccountInterface $account = NULL) {
    if (!$account) {
      $account = $GLOBALS['user'];
    }
    return $account;
  }

}
