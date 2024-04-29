<?php

namespace Drupal\locale;

@trigger_error('The ' . __NAMESPACE__ . '\Locale is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3437110', E_USER_DEPRECATED);

/**
 * Static service container wrapper for locale.
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0.
 *   There is no replacement.
 *
 * @see https://www.drupal.org/node/3437110
 */
class Locale {

  /**
   * Returns the locale configuration manager service.
   *
   * Use the locale config manager service for creating locale-wrapped typed
   * configuration objects.
   *
   * @return \Drupal\locale\LocaleConfigManager
   *   The locale configuration manager.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0.
   *   Use \Drupal::service('locale.config_manager') instead.
   *
   * @see https://www.drupal.org/node/3437110
   */
  public static function config() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal::service(\'locale.config_manager\') instead. See https://www.drupal.org/node/3437110', E_USER_DEPRECATED);
    return \Drupal::service('locale.config_manager');
  }

}
