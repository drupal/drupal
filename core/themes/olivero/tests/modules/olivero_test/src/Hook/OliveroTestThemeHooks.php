<?php

declare(strict_types=1);

namespace Drupal\olivero_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for olivero_test.
 */
class OliveroTestThemeHooks {

  /**
   * Implements hook_preprocess_field_multiple_value_form().
   */
  #[Hook('preprocess_field_multiple_value_form')]
  public function preprocessFieldMultipleValueForm(&$variables): void {
    // Set test multiple value form field to disabled.
    if ($variables["element"]["#field_name"] === "field_multiple_value_form_field") {
      $variables['element']['#disabled'] = TRUE;
    }
  }

  /**
   * Implements hook_preprocess_html().
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(&$variables): void {
    $variables['#attached']['library'][] = 'olivero_test/log-errors';
  }

}
