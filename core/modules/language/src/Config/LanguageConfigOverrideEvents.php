<?php

namespace Drupal\language\Config;

/**
 * Defines events for language configuration overrides.
 *
 * @see \Drupal\Core\Config\ConfigCrudEvent
 */
final class LanguageConfigOverrideEvents {

  /**
   * The name of the event fired when saving the configuration override.
   *
   * This event allows you to perform custom actions whenever a language config
   * override is saved. The event listener method receives a
   * \Drupal\language\Config\LanguageConfigOverrideCrudEvent instance.
   *
   * @Event
   *
   * @see \Drupal\language\Config\LanguageConfigOverrideCrudEvent
   * @see \Drupal\language\Config\LanguageConfigOverride::save()
   * @see \Drupal\locale\LocaleConfigSubscriber
   */
  const SAVE_OVERRIDE = 'language.save_override';

  /**
   * The name of the event fired when deleting the configuration override.
   *
   * This event allows you to perform custom actions whenever a language config
   * override is deleted. The event listener method receives a
   * \Drupal\language\Config\LanguageConfigOverrideCrudEvent instance.
   *
   * @Event
   *
   * @see \Drupal\language\Config\LanguageConfigOverrideCrudEvent
   * @see \Drupal\language\Config\LanguageConfigOverride::delete()
   * @see \Drupal\locale\LocaleConfigSubscriber
   */
  const DELETE_OVERRIDE = 'language.delete_override';

}
