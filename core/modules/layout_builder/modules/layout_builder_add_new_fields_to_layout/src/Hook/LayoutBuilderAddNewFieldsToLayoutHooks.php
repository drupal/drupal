<?php

namespace Drupal\layout_builder_add_new_fields_to_layout\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for layout_builder_add_new_fields_to_layout.
 */
class LayoutBuilderAddNewFieldsToLayoutHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.layout_builder_add_new_fields_to_layout':
        $output = '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Layout Builder Add New Fields to Layout module is a Feature Flag module which, when enabled, adds new fields to layouts.') . '</p>';
        $output .= '<p>' . $this->t('Installing this module means that new fields will automatically be added to layouts, which is easy to miss and often is not the desired outcome. It is recommended to uninstall this if possible.') . '</p>';
        return $output;
    }
    return NULL;
  }

}
