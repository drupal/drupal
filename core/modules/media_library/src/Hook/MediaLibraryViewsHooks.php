<?php

namespace Drupal\media_library\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for media_library.
 */
class MediaLibraryViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    $data = [];
    $data['media']['media_library_select_form'] = [
      'title' => $this->t('Select media'),
      'help' => $this->t('Provides a field for selecting media entities in our media library view'),
      'real field' => 'mid',
      'field' => [
        'id' => 'media_library_select_form',
      ],
    ];
    return $data;
  }

}
