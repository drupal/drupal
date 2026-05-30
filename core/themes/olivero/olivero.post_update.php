<?php

/**
 * @file
 * Post update functions for Olivero.
 */

/**
 * Implements hook_removed_post_updates().
 */
function olivero_removed_post_updates(): array {
  return [
    'olivero_post_update_add_olivero_primary_color' => '11.0.0',
  ];
}

/**
 * Remove shortcut settings if shortcut module is not installed.
 */
function olivero_post_update_remove_shortcut_settings_if_not_installed(): void {
  $settings = \Drupal::configFactory()->getEditable('olivero.settings');

  $settings->clear('third_party_settings.shortcut')
    ->save();

  if (empty($settings->get('third_party_settings'))) {
    $settings->clear('third_party_settings')
      ->save();
  }
}
