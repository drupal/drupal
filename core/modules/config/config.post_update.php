<?php

/**
 * @file
 * Post update functions for config.
 */

/**
 * Fix core.menu.static_menu_link_overrides:definitions.*.parent value to null.
 */
function config_post_update_set_menu_parent_value_to_null(): void {
  $config = \Drupal::configFactory()->getEditable('core.menu.static_menu_link_overrides');
  $all_overrides = $config->get('definitions') ?: [];
  foreach ($all_overrides as $definition_key => $definition_value) {
    if ($definition_value['parent'] === '') {
      $config->set('definitions.' . $definition_key . '.parent', NULL)->save();
    }
  }
}
