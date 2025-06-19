<?php

declare(strict_types=1);

namespace Drupal\navigation_test\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementations for navigation_test module.
 */
class NavigationTestThemeHooks {

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_block__navigation')]
  public function preprocessBlockNavigation(&$variables): void {
    // Add some additional classes so we can target the correct contextual link
    // in tests.
    $variables['attributes']['class'][] = Html::cleanCssIdentifier('block-' . $variables['elements']['#plugin_id']);
  }

}
