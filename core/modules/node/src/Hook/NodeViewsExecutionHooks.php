<?php

namespace Drupal\node\Hook;

use Drupal\views\ViewExecutable;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for node.
 */
class NodeViewsExecutionHooks {

  /**
   * Implements hook_views_query_substitutions().
   */
  #[Hook('views_query_substitutions')]
  public function viewsQuerySubstitutions(ViewExecutable $view) {
    $account = \Drupal::currentUser();
    return [
      '***ADMINISTER_NODES***' => intval($account->hasPermission('administer nodes')),
      '***VIEW_OWN_UNPUBLISHED_NODES***' => intval($account->hasPermission('view own unpublished content')),
      '***BYPASS_NODE_ACCESS***' => intval($account->hasPermission('bypass node access')),
    ];
  }

}
