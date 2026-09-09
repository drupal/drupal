<?php

declare(strict_types=1);

namespace Drupal\stark_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for stark_test.
 */
class StarkTestThemeHooks {

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
    $variables['#attached']['library'][] = 'stark_test/log-errors';
  }

  /**
   * Implements hook_preprocess_menu().
   */
  #[Hook('preprocess_menu')]
  public function preprocessMenu(array &$variables): void {
    $this->setActiveTrailClass($variables['items']);
  }

  /**
   * Recursively adds the active trail class to menu items.
   *
   * @param array $items
   *   Menu items array, possibly nested via 'below'.
   */
  private function setActiveTrailClass(array $items): void {
    foreach ($items as $item) {
      if (!empty($item['in_active_trail'])) {
        $item['attributes']->addClass('menu__item--active-trail');
      }
      if (!empty($item['below'])) {
        $this->setActiveTrailClass($item['below']);
      }
    }
  }

}
