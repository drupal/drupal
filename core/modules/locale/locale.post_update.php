<?php

/**
 * @file
 * Post-update functions for Locale module.
 */

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implements hook_removed_post_updates().
 */
function locale_removed_post_updates(): array {
  return [
    'locale_post_update_clear_cache_for_old_translations' => '9.0.0',
  ];
}

/**
 * Removes the translation.path config.
 */
function locale_post_update_clear_translation_path_config(): ?TranslatableMarkup {
  $config = \Drupal::configFactory()->getEditable('locale.settings');
  $path = $config->get('translation.path');
  $file_system = \Drupal::service(FileSystemInterface::class);
  if ($path !== NULL) {
    if ($file_system->realpath($path) !== $file_system->realpath(Settings::get('locale_translation_path', 'public://translations'))) {
      return t("The configuration locale.setting:translation.path is deprecated and is set to non-default value @path. Support for setting this through configuration will be removed in Drupal 13.0.0 and must set in settings.php: \$settings['locale_translation_path'] = '@path';, see https://www.drupal.org/node/3571594.", [
        '@path' => $path,
      ]);
    }
    else {
      $config->clear('translation.path');
      $config->save();
    }
  }
  return NULL;
}
