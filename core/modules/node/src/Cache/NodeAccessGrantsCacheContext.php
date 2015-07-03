<?php

/**
 * @file
 * Contains \Drupal\node\Cache\NodeAccessGrantsCacheContext.
 */

namespace Drupal\node\Cache;

use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Cache\Context\UserCacheContext;

/**
 * Defines the node access view cache context service.
 *
 * Cache context ID: 'user.node_grants' (to vary by all operations' grants).
 * Calculated cache context ID: 'user.node_grants:%operation', e.g.
 * 'user.node_grants:view' (to vary by the view operation's grants).
 *
 * This allows for node access grants-sensitive caching when listing nodes.
 *
 * @see node_query_node_access_alter()
 * @ingroup node_access
 */
class NodeAccessGrantsCacheContext extends UserCacheContext implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Content access view grants");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($operation = NULL) {
    // If the current user either can bypass node access then we don't need to
    // determine the exact node grants for the current user.
    if ($this->user->hasPermission('bypass node access')) {
      return 'all';
    }

    // When no specific operation is specified, check the grants for all three
    // possible operations.
    if ($operation === NULL) {
      $result = [];
      foreach (['view', 'update', 'delete'] as $op) {
        $result[] = $this->checkNodeGrants($op);
      }
      return implode('-', $result);
    }
    else {
      return $this->checkNodeGrants($operation);
    }
  }

  /**
   * Checks the node grants for the given operation.
   *
   * @param string $operation
   *   The operation to check the node grants for.
   *
   * @return string
   *   The string representation of the cache context.
   */
  protected function checkNodeGrants($operation) {
    // When checking the grants for the 'view' operation and the current user
    // has a global view grant (i.e. a view grant for node ID 0) — note that
    // this is automatically the case if no node access modules exist (no
    // hook_node_grants() implementations) then we don't need to determine the
    // exact node view grants for the current user.
    if ($operation === 'view' && node_access_view_all_nodes($this->user)) {
      return 'view.all';
    }

    $grants = node_access_grants($operation, $this->user);
    $grants_context_parts = [];
    foreach ($grants as $realm => $gids) {
      $grants_context_parts[] = $realm . ':' . implode(',', $gids);
    }
    return $operation . '.' . implode(';', $grants_context_parts);
  }

}
