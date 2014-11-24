<?php

/**
 * @file
 * Contains \Drupal\language\Config\LanguageConfigOverrideEvents.
 */

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
   * @see \Drupal\language\Config\LanguageConfigOverrideCrudEvent
   * @see \Drupal\language\Config\LanguageConfigOverride::save()
   */
  const SAVE_OVERRIDE = 'language.save_override';

  /**
   * The name of the event fired when deleting the configuration override.
   *
   * @see \Drupal\language\Config\LanguageConfigOverrideCrudEvent
   * @see \Drupal\language\Config\LanguageConfigOverride::delete()
   */
  const DELETE_OVERRIDE = 'language.delete_override';

}
