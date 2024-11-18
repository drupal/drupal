<?php

namespace Drupal\contextual\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for contextual.
 */
class ContextualViewsHooks {
  /**
   * @file
   * Provide views data for contextual.module.
   */

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(&$data): void {
    $data['views']['contextual_links'] = [
      'title' => t('Contextual Links'),
      'help' => t('Display fields in a contextual links menu.'),
      'field' => [
        'id' => 'contextual_links',
      ],
    ];
  }

}
