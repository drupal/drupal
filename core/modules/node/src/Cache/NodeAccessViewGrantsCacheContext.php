<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\NodeAccessViewGrantsCacheContext.
 */

namespace Drupal\node\Cache;

use Drupal\Core\Cache\CacheContextInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the node access view grants cache context service.
 *
 * This allows for node access grants-sensitive caching when viewing nodes.
 *
 * @see node_query_node_access_alter()
 */
class NodeAccessViewGrantsCacheContext implements CacheContextInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructs a new NodeAccessViewGrantsCacheContext service.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(AccountInterface $user) {
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Content access view grants");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // If the current user either:
    // - can bypass node access
    // - has a global view grant (such as a view grant for node ID 0) — note
    //   that this is automatically the case if no node access modules exist (no
    //   hook_node_grants() implementations)
    // then we don't need to determine the exact node view grants for the
    // current user.
    if ($this->user->hasPermission('bypass node access') || node_access_view_all_nodes($this->user)) {
      return 'all';
    }

    $grants = node_access_grants('view', $this->user);
    $grants_context_parts = [];
    foreach ($grants as $realm => $gids) {
      $grants_context_parts[] = $realm . ':' . implode(',', $gids);
    }
    return implode(';', $grants_context_parts);
  }

}
