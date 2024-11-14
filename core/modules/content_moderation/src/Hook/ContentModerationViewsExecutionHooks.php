<?php

namespace Drupal\content_moderation\Hook;

use Drupal\views\ViewExecutable;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for content_moderation.
 */
class ContentModerationViewsExecutionHooks {

  /**
   * Implements hook_views_query_substitutions().
   */
  #[Hook('views_query_substitutions')]
  public function viewsQuerySubstitutions(ViewExecutable $view) {
    $account = \Drupal::currentUser();
    return [
      '***VIEW_ANY_UNPUBLISHED_NODES***' => intval($account->hasPermission('view any unpublished content')),
    ];
  }

}
