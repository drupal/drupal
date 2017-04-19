<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a default implementation for entity access control handler.
 */
class EntityAccessControlHandler extends EntityHandlerBase implements EntityAccessControlHandlerInterface {

  /**
   * Stores calculated access check results.
   *
   * @var array
   */
  protected $accessCache = [];

  /**
   * The entity type ID of the access control handler instance.
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
   * Allows to grant access to just the labels.
   *
   * By default, the "view label" operation falls back to "view". Set this to
   * TRUE to allow returning different access when just listing entity labels.
   *
   * @var bool
   */
  protected $viewLabelOperation = FALSE;

  /**
   * Constructs an access control handler instance.
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
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $langcode = $entity->language()->getId();

    if ($operation === 'view label' && $this->viewLabelOperation == FALSE) {
      $operation = 'view';
    }

    if (($return = $this->getCache($entity->uuid(), $operation, $langcode, $account)) !== NULL) {
      // Cache hit, no work necessary.
      return $return_as_object ? $return : $return->isAllowed();
    }

    // Invoke hook_entity_access() and hook_ENTITY_TYPE_access(). Hook results
    // take precedence over overridden implementations of
    // EntityAccessControlHandler::checkAccess(). Entities that have checks that
    // need to be done before the hook is invoked should do so by overriding
    // this method.

    // We grant access to the entity if both of these conditions are met:
    // - No modules say to deny access.
    // - At least one module says to grant access.
    $access = array_merge(
      $this->moduleHandler()->invokeAll('entity_access', [$entity, $operation, $account]),
      $this->moduleHandler()->invokeAll($entity->getEntityTypeId() . '_access', [$entity, $operation, $account])
    );

    $return = $this->processAccessHookResults($access);

    // Also execute the default access check except when the access result is
    // already forbidden, as in that case, it can not be anything else.
    if (!$return->isForbidden()) {
      $return = $return->orIf($this->checkAccess($entity, $operation, $account));
    }
    $result = $this->setCache($return, $entity->uuid(), $operation, $langcode, $account);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * We grant access to the entity if both of these conditions are met:
   * - No modules say to deny access.
   * - At least one module says to grant access.
   *
   * @param \Drupal\Core\Access\AccessResultInterface[] $access
   *   An array of access results of the fired access hook.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The combined result of the various access checks' results. All their
   *   cacheability metadata is merged as well.
   *
   * @see \Drupal\Core\Access\AccessResultInterface::orIf()
   */
  protected function processAccessHookResults(array $access) {
    // No results means no opinion.
    if (empty($access)) {
      return AccessResult::neutral();
    }

    /** @var \Drupal\Core\Access\AccessResultInterface $result */
    $result = array_shift($access);
    foreach ($access as $other) {
      $result = $result->orIf($other);
    }
    return $result;
  }

  /**
   * Performs access checks.
   *
   * This method is supposed to be overwritten by extending classes that
   * do their own custom access checking.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'view label', 'update' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'delete' && $entity->isNew()) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
    if ($admin_permission = $this->entityType->getAdminPermission()) {
      return AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission());
    }
    else {
      // No opinion.
      return AccessResult::neutral();
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
   * @return \Drupal\Core\Access\AccessResultInterface|null
   *   The cached AccessResult, or NULL if there is no record for the given
   *   user, operation, langcode and entity in the cache.
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
   * @param \Drupal\Core\Access\AccessResultInterface $access
   *   The access result.
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
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether the user has access, plus cacheability metadata.
   */
  protected function setCache($access, $cid, $operation, $langcode, AccountInterface $account) {
    // Save the given value in the static cache and directly return it.
    return $this->accessCache[$account->id()][$cid][$langcode][$operation] = $access;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $this->accessCache = [];
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $context += [
      'entity_type_id' => $this->entityTypeId,
      'langcode' => LanguageInterface::LANGCODE_DEFAULT,
    ];

    $cid = $entity_bundle ? 'create:' . $entity_bundle : 'create';
    if (($access = $this->getCache($cid, 'create', $context['langcode'], $account)) !== NULL) {
      // Cache hit, no work necessary.
      return $return_as_object ? $access : $access->isAllowed();
    }

    // Invoke hook_entity_create_access() and hook_ENTITY_TYPE_create_access().
    // Hook results take precedence over overridden implementations of
    // EntityAccessControlHandler::checkCreateAccess(). Entities that have
    // checks that need to be done before the hook is invoked should do so by
    // overriding this method.

    // We grant access to the entity if both of these conditions are met:
    // - No modules say to deny access.
    // - At least one module says to grant access.
    $access = array_merge(
      $this->moduleHandler()->invokeAll('entity_create_access', [$account, $context, $entity_bundle]),
      $this->moduleHandler()->invokeAll($this->entityTypeId . '_create_access', [$account, $context, $entity_bundle])
    );

    $return = $this->processAccessHookResults($access);

    // Also execute the default access check except when the access result is
    // already forbidden, as in that case, it can not be anything else.
    if (!$return->isForbidden()) {
      $return = $return->orIf($this->checkCreateAccess($account, $context, $entity_bundle));
    }
    $result = $this->setCache($return, $cid, 'create', $context['langcode'], $account);
    return $return_as_object ? $result : $result->isAllowed();
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
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($admin_permission = $this->entityType->getAdminPermission()) {
      return AccessResult::allowedIfHasPermission($account, $admin_permission);
    }
    else {
      // No opinion.
      return AccessResult::neutral();
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
      $account = \Drupal::currentUser();
    }
    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account = NULL, FieldItemListInterface $items = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    // Get the default access restriction that lives within this field.
    $default = $items ? $items->defaultAccess($operation, $account) : AccessResult::allowed();

    // Explicitly disallow changing the entity ID and entity UUID.
    if ($operation === 'edit') {
      if ($field_definition->getName() === $this->entityType->getKey('id')) {
        return $return_as_object ? AccessResult::forbidden('The entity ID cannot be changed') : FALSE;
      }
      elseif ($field_definition->getName() === $this->entityType->getKey('uuid')) {
        // UUIDs can be set when creating an entity.
        if ($items && ($entity = $items->getEntity()) && !$entity->isNew()) {
          return $return_as_object ? AccessResult::forbidden('The entity UUID cannot be changed')->addCacheableDependency($entity) : FALSE;
        }
      }
    }

    // Get the default access restriction as specified by the access control
    // handler.
    $entity_default = $this->checkFieldAccess($operation, $field_definition, $account, $items);

    // Combine default access, denying access wins.
    $default = $default->andIf($entity_default);

    // Invoke hook and collect grants/denies for field access from other
    // modules. Our default access flag is masked under the ':default' key.
    $grants = [':default' => $default];
    $hook_implementations = $this->moduleHandler()->getImplementations('entity_field_access');
    foreach ($hook_implementations as $module) {
      $grants = array_merge($grants, [$module => $this->moduleHandler()->invoke($module, 'entity_field_access', [$operation, $field_definition, $account, $items])]);
    }

    // Also allow modules to alter the returned grants/denies.
    $context = [
      'operation' => $operation,
      'field_definition' => $field_definition,
      'items' => $items,
      'account' => $account,
    ];
    $this->moduleHandler()->alter('entity_field_access', $grants, $context);

    $result = $this->processAccessHookResults($grants);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * Default field access as determined by this access control handler.
   *
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view" or "edit".
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   (optional) The field values for which to check access, or NULL if access
   *   is checked for the field definition, without any specific value
   *   available. Defaults to NULL.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    return AccessResult::allowed();
  }

}
