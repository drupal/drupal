<?php

/**
 * @file
 * Contains \Drupal\node\Access\NodeRevisionAccessCheck.
 */

namespace Drupal\node\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for node revisions.
 *
 * @ingroup node_access
 */
class NodeRevisionAccessCheck implements AccessInterface {

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The node access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $nodeAccess;

  /**
   * A static cache of access checks.
   *
   * @var array
   */
  protected $access = array();

  /**
   * Constructs a new NodeRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->nodeStorage = $entity_manager->getStorage('node');
    $this->nodeAccess = $entity_manager->getAccessControlHandler('node');
  }

  /**
   * Checks routing access for the node revision.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $node_revision
   *   (optional) The node revision ID. If not specified, but $node is, access
   *   is checked for that object's revision.
   * @param \Drupal\node\NodeInterface $node
   *   (optional) A node object. Used for checking access to a node's default
   *   revision when $node_revision is unspecified. Ignored when $node_revision
   *   is specified. If neither $node_revision nor $node are specified, then
   *   access is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, $node_revision = NULL, NodeInterface $node = NULL) {
    if ($node_revision) {
      $node = $this->nodeStorage->loadRevision($node_revision);
    }
    $operation = $route->getRequirement('_access_node_revision');
    return AccessResult::allowedIf($node && $this->checkAccess($node, $account, $operation))->cachePerPermissions();
  }

  /**
   * Checks node revision access.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $op
   *   (optional) The specific operation being checked. Defaults to 'view.'
   * @param string|null $langcode
   *   (optional) Language code for the variant of the node. Different language
   *   variants might have different permissions associated. If NULL, the
   *   original langcode of the node is used. Defaults to NULL.
   *
   * @return bool
   *   TRUE if the operation may be performed, FALSE otherwise.
   */
  public function checkAccess(NodeInterface $node, AccountInterface $account, $op = 'view', $langcode = NULL) {
    $map = array(
      'view' => 'view all revisions',
      'update' => 'revert all revisions',
      'delete' => 'delete all revisions',
    );
    $bundle = $node->bundle();
    $type_map = array(
      'view' => "view $bundle revisions",
      'update' => "revert $bundle revisions",
      'delete' => "delete $bundle revisions",
    );

    if (!$node || !isset($map[$op]) || !isset($type_map[$op])) {
      // If there was no node to check against, or the $op was not one of the
      // supported ones, we return access denied.
      return FALSE;
    }

    // If no language code was provided, default to the node revision's langcode.
    if (empty($langcode)) {
      $langcode = $node->language()->getId();
    }

    // Statically cache access by revision ID, language code, user account ID,
    // and operation.
    $cid = $node->getRevisionId() . ':' . $langcode . ':' . $account->id() . ':' . $op;

    if (!isset($this->access[$cid])) {
      // Perform basic permission checks first.
      if (!$account->hasPermission($map[$op]) && !$account->hasPermission($type_map[$op]) && !$account->hasPermission('administer nodes')) {
        $this->access[$cid] = FALSE;
        return FALSE;
      }

      // There should be at least two revisions. If the vid of the given node
      // and the vid of the default revision differ, then we already have two
      // different revisions so there is no need for a separate database check.
      // Also, if you try to revert to or delete the default revision, that's
      // not good.
      if ($node->isDefaultRevision() && ($this->nodeStorage->countDefaultLanguageRevisions($node) == 1 || $op == 'update' || $op == 'delete')) {
        $this->access[$cid] = FALSE;
      }
      elseif ($account->hasPermission('administer nodes')) {
        $this->access[$cid] = TRUE;
      }
      else {
        // First check the access to the default revision and finally, if the
        // node passed in is not the default revision then access to that, too.
        $this->access[$cid] = $this->nodeAccess->access($this->nodeStorage->load($node->id()), $op, $langcode, $account) && ($node->isDefaultRevision() || $this->nodeAccess->access($node, $op, $langcode, $account));
      }
    }

    return $this->access[$cid];
  }

}
