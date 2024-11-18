<?php

declare(strict_types=1);

namespace Drupal\link_generation_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for link_generation_test.
 */
class LinkGenerationTestHooks {

  /**
   * Implements hook_link_alter().
   */
  #[Hook('link_alter')]
  public function linkAlter(&$variables): void {
    if (\Drupal::state()->get('link_generation_test_link_alter', FALSE)) {
      // Add a text to the end of links.
      if (\Drupal::state()->get('link_generation_test_link_alter_safe', FALSE)) {
        $variables['text'] = t('@text <strong>Test!</strong>', ['@text' => $variables['text']]);
      }
      else {
        $variables['text'] .= ' <strong>Test!</strong>';
      }
    }
  }

}
