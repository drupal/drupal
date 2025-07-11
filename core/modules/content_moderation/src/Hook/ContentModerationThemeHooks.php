<?php

namespace Drupal\content_moderation\Hook;

use Drupal\content_moderation\ContentPreprocess;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for content_moderation.
 */
class ContentModerationThemeHooks {

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(&$variables): void {
    \Drupal::service('class_resolver')->getInstanceFromDefinition(ContentPreprocess::class)->preprocessNode($variables);
  }

}
