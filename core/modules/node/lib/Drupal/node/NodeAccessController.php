<?php

/**
 * @file
 * Contains \Drupal\node\NodeAccessController.
 */

namespace Drupal\node;

use Drupal\user\Plugin\Core\Entity\User;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityNG;

/**
 * Defines the access controller for the node entity type.
 */
class NodeAccessController extends EntityAccessController {

  /**
   * Overrides \Drupal\Core\Entity\EntityAccessController::viewAccess().
   */
  public function viewAccess(EntityInterface $node, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    if (($cached = $this->getCache($node, 'view', $langcode, $account)) !== NULL ) {
      return $cached;
    }

    if (($access = $this->access($node, 'view', $langcode, $account)) !== NULL) {
      return $this->setCache((bool) $access, $node, 'view', $langcode, $account);
    };

    // If no modules implement hook_node_grants(), the default behavior is to
    // allow all users to view published nodes, so reflect that here.
    $status = $node instanceof EntityNG ? $node->getTranslation($langcode, FALSE)->status->value : $node->status;
    return $this->setCache($status, $node, 'view', $langcode, $account);
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityAccessController::access().
   */
  protected function access(EntityInterface $node, $operation, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    if (user_access('bypass node access', $account)) {
      return TRUE;
    }

    if (!user_access('access content', $account)) {
      return FALSE;
    }

    if (($access = parent::access($node, $operation, $langcode, $account)) !== NULL) {
      return (bool) $access;
    };

    // Fetch information from the node object if possible.
    $status = isset($node->status) ? $node->status : NULL;
    $uid = isset($node->uid) ? $node->uid : NULL;
    // If it is a proper EntityNG object, use the proper methods.
    if ($node instanceof EntityNG) {
      $status = $node->getTranslation($langcode, FALSE)->status->value;
      $uid = $node->getTranslation($langcode, FALSE)->uid->value;
    }

    // Check if authors can view their own unpublished nodes.
    if ($operation == 'view' && !$status && user_access('view own unpublished content', $account)) {
      // @todo Remove this once we can rely on $account.
      if (!$account) {
        $account = user_load($GLOBALS['user']->uid);
      }

      if ($account->id() != 0 && $account->id() == $uid) {
        return TRUE;
      }
    }

    // If no module specified either allow or deny, we fall back to the
    // node_access table.
    if (($grants = $this->accessGrants($node, $operation, $langcode, $account)) !== NULL) {
      return (bool) $grants;
    }
  }

  /**
   * Determines access to nodes based on node grants.
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
   *   TRUE if access was granted, FALSE if access was denied or NULL if no
   *   module implements hook_node_grants(), the node does not (yet) have an id
   *   or none of the implementing modules explicitly granted or denied access.
   */
  protected function accessGrants(EntityInterface $node, $operation, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    // If no module implements the hook or the node does not have an id there is
    // no point in querying the database for access grants.
    if (!module_implements('node_grants') || !$node->id()) {
      return;
    }

    // Check the database for potential access grants.
    $query = db_select('node_access');
    $query->addExpression('1');
    // Only interested for granting in the current operation.
    $query->condition('grant_' . $operation, 1, '>=');
    // Check for grants for this node and the correct langcode.
    $nids = db_and()
      ->condition('nid', $node->nid)
      ->condition('langcode', $langcode);
    // If the node is published, also take the default grant into account. The
    // default is saved with a node ID of 0.
    $status = $node instanceof EntityNG ? $node->status : $node->get('status', $langcode)->value;
    if ($status) {
      $nids = db_or()
        ->condition($nids)
        ->condition('nid', 0);
    }
    $query->condition($nids);
    $query->range(0, 1);

    $grants = db_or();
    foreach (node_access_grants($operation, $account) as $realm => $gids) {
      foreach ($gids as $gid) {
        $grants->condition(db_and()
          ->condition('gid', $gid)
          ->condition('realm', $realm));
      }
    }

    if (count($grants) > 0) {
      $query->condition($grants);
    }

    return $query->execute()->fetchField();
  }

}
