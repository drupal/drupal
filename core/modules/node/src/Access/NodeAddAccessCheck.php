<?php

namespace Drupal\node\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Determines access to for node add pages.
 *
 * @ingroup node_access
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
 *   _entity_create_access or _entity_create_any_access access checks instead.
 *
 * @see https://www.drupal.org/node/2836069
 */
class NodeAddAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the node add page for the node type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   (optional) The node type. If not specified, access is allowed if there
   *   exists at least one node type for which the user may create a node.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, NodeTypeInterface $node_type = NULL) {
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('node');
    // If checking whether a node of a particular type may be created.
    if ($account->hasPermission('administer content types')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    if ($node_type) {
      return $access_control_handler->createAccess($node_type->id(), $account, [], TRUE);
    }
    // If checking whether a node of any type may be created.
    foreach ($this->entityTypeManager->getStorage('node_type')->loadMultiple() as $node_type) {
      if (($access = $access_control_handler->createAccess($node_type->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
