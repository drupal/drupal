<?php

namespace Drupal\contextual\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for contextual.
 */
class ContextualViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(&$data): void {
    $data['views']['contextual_links'] = [
      'title' => $this->t('Contextual Links'),
      'help' => $this->t('Display fields in a contextual links menu.'),
      'field' => [
        'id' => 'contextual_links',
      ],
    ];
  }

}
