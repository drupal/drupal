<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityAccessController.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a default implementation for entity access controllers.
 */
class EntityAccessController extends EntityControllerBase implements EntityAccessControllerInterface {

  /**
   * Stores calculcated access check results.
   *
   * @var array
   */
  protected $accessCache = array();

  /**
   * The entity type ID of the access controller instance.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * Constructs an access controller instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  public function __construct(EntityTypeInterface $entity_type) {
    $this->entityTypeId = $entity_type->id();
    $this->entityType = $entity_type;
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

    // Invoke hook_entity_access() and hook_ENTITY_TYPE_access(). Hook results
    // take precedence over overridden implementations of
    // EntityAccessController::checkAccess(). Entities that have checks that
    // need to be done before the hook is invoked should do so by overriding
    // this method.

    // We grant access to the entity if both of these conditions are met:
    // - No modules say to deny access.
    // - At least one module says to grant access.
    $access = array_merge(
      $this->moduleHandler()->invokeAll('entity_access', array($entity, $operation, $account, $langcode)),
      $this->moduleHandler()->invokeAll($entity->getEntityTypeId() . '_access', array($entity, $operation, $account, $langcode))
    );

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
   * @return bool|null
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
    if ($admin_permission = $this->entityType->getAdminPermission()) {
      return $account->hasPermission($admin_permission);
    }
    else {
      return NULL;
    }
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

    // Invoke hook_entity_create_access() and hook_ENTITY_TYPE_create_access().
    // Hook results take precedence over overridden implementations of
    // EntityAccessController::checkAccess(). Entities that have checks that
    // need to be done before the hook is invoked should do so by overriding
    // this method.

    // We grant access to the entity if both of these conditions are met:
    // - No modules say to deny access.
    // - At least one module says to grant access.
    $access = array_merge(
      $this->moduleHandler()->invokeAll('entity_create_access', array($account, $context['langcode'])),
      $this->moduleHandler()->invokeAll($this->entityTypeId . '_create_access', array($account, $context['langcode']))
    );

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
    if ($admin_permission = $this->entityType->getAdminPermission()) {
      return $account->hasPermission($admin_permission);
    }
    else {
      return NULL;
    }
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

  /**
   * {@inheritdoc}
   */
  public function fieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account = NULL, FieldItemListInterface $items = NULL) {
    $account = $this->prepareUser($account);

    // Get the default access restriction that lives within this field.
    $default = $items ? $items->defaultAccess($operation, $account) : TRUE;

    // Invoke hook and collect grants/denies for field access from other
    // modules. Our default access flag is masked under the ':default' key.
    $grants = array(':default' => $default);
    $hook_implementations = $this->moduleHandler()->getImplementations('entity_field_access');
    foreach ($hook_implementations as $module) {
      $grants = array_merge($grants, array($module => $this->moduleHandler()->invoke($module, 'entity_field_access', array($operation, $field_definition, $account, $items))));
    }

    // Also allow modules to alter the returned grants/denies.
    $context = array(
      'operation' => $operation,
      'field_definition' => $field_definition,
      'items' => $items,
      'account' => $account,
    );
    $this->moduleHandler()->alter('entity_field_access', $grants, $context);

    // One grant being FALSE is enough to deny access immediately.
    if (in_array(FALSE, $grants, TRUE)) {
      return FALSE;
    }
    // At least one grant has the explicit opinion to allow access.
    if (in_array(TRUE, $grants, TRUE)) {
      return TRUE;
    }
    // All grants are NULL and have no opinion - deny access in that case.
    return FALSE;
  }

}
