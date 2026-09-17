<?php

/**
 * @file
 * Post update functions for Default Admin.
 */

/**
 * Renames legacy focus color preset values in theme and user settings.
 */
function default_admin_post_update_rename_focus_color_presets(): void {
  $renamed = [
    'gin' => 'default',
    'claro' => 'legacy_green',
  ];

  $config = \Drupal::configFactory()->getEditable('default_admin.settings');
  $preset = $config->get('preset_focus_color');
  if (is_string($preset) && isset($renamed[$preset])) {
    $config->set('preset_focus_color', $renamed[$preset])->save();
  }

  if (!\Drupal::hasService('user.data')) {
    return;
  }
  /** @var \Drupal\user\UserDataInterface $user_data */
  $user_data = \Drupal::service('user.data');
  foreach ($user_data->get('default_admin') as $uid => $data) {
    // User overrides are stored as one array under the 'settings' key.
    $preset = $data['settings']['preset_focus_color'] ?? NULL;
    if (is_string($preset) && isset($renamed[$preset])) {
      $data['settings']['preset_focus_color'] = $renamed[$preset];
      $user_data->set('default_admin', $uid, 'settings', $data['settings']);
    }
    // Older overrides were stored under one key per setting.
    $preset = $data['preset_focus_color'] ?? NULL;
    if (is_string($preset) && isset($renamed[$preset])) {
      $user_data->set('default_admin', $uid, 'preset_focus_color', $renamed[$preset]);
    }
  }
}
