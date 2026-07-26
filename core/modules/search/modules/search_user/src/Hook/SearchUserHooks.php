<?php

namespace Drupal\search_user\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for Search User.
 */
class SearchUserHooks {

  /**
   * Implements hook_search_plugin_alter().
   */
  #[Hook('search_plugin_alter')]
  public function searchPluginAlter(array &$definitions): void {
    if (isset($definitions['user_search'])) {
      $definitions['user_search']['class'] = 'Drupal\search_user\Plugin\Search\SearchUser';
    }
  }

}
