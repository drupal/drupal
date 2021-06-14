<?php

namespace Drupal\locale;

/**
 * Static service container wrapper for locale.
 */
class Locale {

  /**
   * Returns the locale configuration manager service.
   *
   * Use the locale config manager service for creating locale-wrapped typed
   * configuration objects.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create()
   *
   * @return \Drupal\locale\LocaleConfigManager
   */
  public static function config() {
    return \Drupal::service('locale.config_manager');
  }

}
