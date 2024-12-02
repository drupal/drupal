<?php

namespace Drupal\content_moderation\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for content_moderation.
 */
class ContentModerationViewsHooks {

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    return _content_moderation_views_data_object()->getViewsData();
  }

}
