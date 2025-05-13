<?php

namespace Drupal\node;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the node entity type.
 *
 * @see \Drupal\node\Entity\Node
 * @ingroup node_access
 */
class NodeAccessControlHandler extends EntityAccessControlHandler implements NodeAccessControlHandlerInterface, EntityHandlerInterface {

  /**
   * The node grant storage.
   *
   * @var \Drupal\node\NodeGrantDatabaseStorageInterface
   */
  protected $grantStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Map of revision operations.
   *
   * Keys contain revision operations, where values are an array containing the
   * permission operation and entity operation.
   *
   * Permission operation is used to build the required permission, e.g.
   * 'permissionOperation all revisions', 'permissionOperation type revisions'.
   *
   * Entity operation is used to determine access, e.g for 'delete revision'
   * operation, an account must also have access to 'delete' operation on an
   * entity.
   */
  protected const REVISION_OPERATION_MAP = [
    'view all revisions' => ['view', 'view'],
    'view revision' => ['view', 'view'],
    'revert revision' => ['revert', 'update'],
    'delete revision' => ['delete', 'delete'],
  ];

  /**
   * Constructs a NodeAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\node\NodeGrantDatabaseStorageInterface $grant_storage
   *   The node grant storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, NodeGrantDatabaseStorageInterface $grant_storage, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);
    $this->grantStorage = $grant_storage;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('node.grant_storage'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    // Only bypass if not a revision operation, to retain compatibility.
    if ($account->hasPermission('bypass node access') && !isset(static::REVISION_OPERATION_MAP[$operation])) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }
    if (!$account->hasPermission('access content')) {
      $result = AccessResult::forbidden("The 'access content' permission is required.")->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }
    $result = parent::access($entity, $operation, $account, TRUE)->cachePerPermissions();

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, ?AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('bypass node access')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }
    if (!$account->hasPermission('access content')) {
      $result = AccessResult::forbidden("The 'access content' permission is required.")->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }

    $result = parent::createAccess($entity_bundle, $account, $context, TRUE)->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $node, $operation, AccountInterface $account) {
    assert($node instanceof NodeInterface);
    $cacheability = new CacheableMetadata();

    /** @var \Drupal\node\NodeInterface $node */
    if ($operation === 'view') {
      $result = $this->checkViewAccess($node, $account, $cacheability);
      if ($result !== NULL) {
        return $result;
      }
    }

    [$revision_permission_operation, $entity_operation] = static::REVISION_OPERATION_MAP[$operation] ?? [
      NULL,
      NULL,
    ];

    // Revision operations.
    if ($revision_permission_operation) {
      $cacheability->addCacheContexts(['user.permissions']);
      $bundle = $node->bundle();

      // If user doesn't have any of these then quit.
      if (!$account->hasPermission("$revision_permission_operation all revisions") && !$account->hasPermission("$revision_permission_operation $bundle revisions") && !$account->hasPermission('administer nodes')) {
        return AccessResult::neutral()->addCacheableDependency($cacheability);
      }

      // If the user has the view all revisions permission and this is the view
      // all revisions operation then we can allow access.
      if ($operation === 'view all revisions') {
        return AccessResult::allowed()->addCacheableDependency($cacheability);
      }

      // If this is the default revision, return access denied for revert or
      // delete operations.
      $cacheability->addCacheableDependency($node);
      if ($node->isDefaultRevision() && ($operation === 'revert revision' || $operation === 'delete revision')) {
        return AccessResult::forbidden()->addCacheableDependency($cacheability);
      }
      elseif ($account->hasPermission('administer nodes')) {
        return AccessResult::allowed()->addCacheableDependency($cacheability);
      }

      // First check the access to the default revision and finally, if the
      // node passed in is not the default revision then check access to
      // that, too.
      $node_storage = $this->entityTypeManager->getStorage($node->getEntityTypeId());
      $access = $this->access($node_storage->load($node->id()), $entity_operation, $account, TRUE);
      if (!$node->isDefaultRevision()) {
        $access = $access->andIf($this->access($node, $entity_operation, $account, TRUE));
      }
      return $access->addCacheableDependency($cacheability);
    }

    // Evaluate node grants.
    $access_result = $this->grantStorage->access($node, $operation, $account);
    if ($access_result instanceof RefinableCacheableDependencyInterface) {
      $access_result->addCacheableDependency($cacheability);
    }
    return $access_result;
  }

  /**
   * Performs view access checks.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   Allows cacheability information bubble up from this method.
   *
   * @return \Drupal\Core\Access\AccessResultInterface|null
   *   The calculated access result or null when no opinion.
   */
  protected function checkViewAccess(NodeInterface $node, AccountInterface $account, CacheableMetadata $cacheability): ?AccessResultInterface {
    // If the node status changes, so does the outcome of the check below, so
    // we need to add the node as a cacheable dependency.
    $cacheability->addCacheableDependency($node);

    if ($node->isPublished()) {
      return NULL;
    }
    $cacheability->addCacheContexts(['user.permissions']);

    if (!$account->hasPermission('view own unpublished content')) {
      return NULL;
    }

    $cacheability->addCacheContexts(['user.roles:authenticated']);
    // The "view own unpublished content" permission must not be granted
    // to anonymous users for security reasons.
    if (!$account->isAuthenticated()) {
      return NULL;
    }

    // When access is granted due to the 'view own unpublished content'
    // permission and for no other reason, node grants are bypassed. However,
    // to ensure the full set of cacheable metadata is available to variation
    // cache, additionally add the node_grants cache context so that if the
    // status or the owner of the node changes, cache redirects will continue to
    // reflect the latest state without needing to be invalidated.
    $cacheability->addCacheContexts(['user']);
    if ($this->moduleHandler->hasImplementations('node_grants')) {
      $cacheability->addCacheContexts(['user.node_grants:view']);
    }
    if ($account->id() != $node->getOwnerId()) {
      return NULL;
    }

    return AccessResult::allowed()->addCacheableDependency($cacheability);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIf($account->hasPermission('create ' . $entity_bundle . ' content'))->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {
    // Only users with the administer nodes permission can edit administrative
    // fields.
    $administrative_fields = ['uid', 'status', 'created', 'promote', 'sticky'];
    if ($operation == 'edit' && in_array($field_definition->getName(), $administrative_fields, TRUE)) {
      return AccessResult::allowedIfHasPermission($account, 'administer nodes');
    }

    // No user can change read only fields.
    $read_only_fields = ['revision_timestamp', 'revision_uid'];
    if ($operation == 'edit' && in_array($field_definition->getName(), $read_only_fields, TRUE)) {
      return AccessResult::forbidden();
    }

    // Users have access to the revision_log field either if they have
    // administrative permissions or if the new revision option is enabled.
    if ($operation == 'edit' && $field_definition->getName() == 'revision_log') {
      if ($account->hasPermission('administer nodes')) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      return AccessResult::allowedIf($items->getEntity()->type->entity->shouldCreateNewRevision())->cachePerPermissions();
    }
    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function acquireGrants(NodeInterface $node) {
    $grants = $this->moduleHandler->invokeAll('node_access_records', [$node]);
    // Let modules alter the grants.
    $this->moduleHandler->alter('node_access_records', $grants, $node);
    // If no grants are set and the node is published, then use the default
    // grant.
    if (empty($grants) && $node->isPublished()) {
      $grants[] = ['realm' => 'all', 'gid' => 0, 'grant_view' => 1, 'grant_update' => 0, 'grant_delete' => 0];
    }
    return $grants;
  }

  /**
   * {@inheritdoc}
   */
  public function writeDefaultGrant() {
    $this->grantStorage->writeDefault();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteGrants() {
    $this->grantStorage->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function countGrants() {
    return $this->grantStorage->count();
  }

  /**
   * {@inheritdoc}
   */
  public function checkAllGrants(AccountInterface $account) {
    return $this->grantStorage->checkAll($account);
  }

}
