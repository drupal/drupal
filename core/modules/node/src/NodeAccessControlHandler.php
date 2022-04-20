<?php

namespace Drupal\node;

use Drupal\Core\Access\AccessResult;
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface|null $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, NodeGrantDatabaseStorageInterface $grant_storage, EntityTypeManagerInterface $entity_type_manager = NULL) {
    parent::__construct($entity_type);
    $this->grantStorage = $grant_storage;
    if (!isset($entity_type_manager)) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $entity_type_manager argument is deprecated in drupal:9.3.0 and will be required in drupal:10.0.0. See https://www.drupal.org/node/3214171', E_USER_DEPRECATED);
      $entity_type_manager = \Drupal::entityTypeManager();
    }
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
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
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
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
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
    /** @var \Drupal\node\NodeInterface $node */

    // Fetch information from the node object if possible.
    $status = $node->isPublished();
    $uid = $node->getOwnerId();

    // Check if authors can view their own unpublished nodes.
    if ($operation === 'view' && !$status && $account->hasPermission('view own unpublished content') && $account->isAuthenticated() && $account->id() == $uid) {
      return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($node);
    }

    [$revision_permission_operation, $entity_operation] = static::REVISION_OPERATION_MAP[$operation] ?? [
      NULL,
      NULL,
    ];

    // Revision operations.
    if ($revision_permission_operation) {
      $bundle = $node->bundle();
      // If user doesn't have any of these then quit.
      if (!$account->hasPermission("$revision_permission_operation all revisions") && !$account->hasPermission("$revision_permission_operation $bundle revisions") && !$account->hasPermission('administer nodes')) {
        return AccessResult::neutral()->cachePerPermissions();
      }

      // If the user has the view all revisions permission and this is the view
      // all revisions operation then we can allow access.
      if ($operation === 'view all revisions') {
        return AccessResult::allowed()->cachePerPermissions();
      }

      // If this is the default revision, return access denied for revert or
      // delete operations.
      if ($node->isDefaultRevision() && ($operation === 'revert revision' || $operation === 'delete revision')) {
        return AccessResult::forbidden()->addCacheableDependency($node);
      }
      elseif ($account->hasPermission('administer nodes')) {
        return AccessResult::allowed()->cachePerPermissions();
      }

      // First check the access to the default revision and finally, if the
      // node passed in is not the default revision then check access to
      // that, too.
      $node_storage = $this->entityTypeManager->getStorage($node->getEntityTypeId());
      $access = $this->access($node_storage->load($node->id()), $entity_operation, $account, TRUE);
      if (!$node->isDefaultRevision()) {
        $access = $access->andIf($this->access($node, $entity_operation, $account, TRUE));
      }
      return $access->cachePerPermissions()->addCacheableDependency($node);
    }

    // Evaluate node grants.
    $access_result = $this->grantStorage->access($node, $operation, $account);
    if ($operation === 'view' && $access_result instanceof RefinableCacheableDependencyInterface) {
      // Node variations can affect the access to the node. For instance, the
      // access result cache varies on the node's published status. Only the
      // 'view' node grant can currently be cached. The 'update' and 'delete'
      // grants are already marked as uncacheable in the node grant storage.
      // @see \Drupal\node\NodeGrantDatabaseStorage::access()
      $access_result->addCacheableDependency($node);
    }
    return $access_result;
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
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
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
    // If no grants are set and the node is published, then use the default grant.
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
