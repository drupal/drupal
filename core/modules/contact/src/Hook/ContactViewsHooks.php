<?php

namespace Drupal\contact\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for contact.
 */
class ContactViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(&$data): void {
    $data['users']['contact'] = [
      'field' => [
        'title' => $this->t('Contact link'),
        'help' => $this->t('Provide a simple link to the user contact page.'),
        'id' => 'contact_link',
      ],
    ];
  }

}
