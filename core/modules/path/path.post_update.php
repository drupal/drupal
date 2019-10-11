<?php

/**
 * @file
 * Post update functions for the path module.
 */

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ContentLanguageSettings;

/**
 * Create the language content settings configuration object for path aliases.
*/
function path_post_update_create_language_content_settings() {
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  if ($entity_definition_update_manager->getEntityType('language_content_settings')) {
    ContentLanguageSettings::loadByEntityTypeBundle('path_alias', 'path_alias')
      ->setDefaultLangcode(LanguageInterface::LANGCODE_NOT_SPECIFIED)
      ->setLanguageAlterable(TRUE)
      ->trustData()
      ->save();
  }
}
