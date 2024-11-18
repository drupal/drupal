<?php

namespace Drupal\contact\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for contact.
 */
class ContactViewsHooks {
  /**
   * @file
   * Provide views data for contact.module.
   */

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(&$data): void {
    $data['users']['contact'] = [
      'field' => [
        'title' => t('Contact link'),
        'help' => t('Provide a simple link to the user contact page.'),
        'id' => 'contact_link',
      ],
    ];
  }

}
