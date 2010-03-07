<?php
// $Id: field.multilingual.inc,v 1.9 2010/03/07 07:44:18 webchick Exp $

/**
 * @file
 * Multilingual field API helper functions.
 */

/**
 * Implements hook_multilingual_settings_changed().
 */
function field_multilingual_settings_changed() {
  field_info_cache_clear();
}

/**
 * Collect the available languages for the given entity type and field.
 *
 * If an entity has a translation handler and the given field is translatable,
 * a (not necessarily strict) subset of the current enabled languages will be
 * returned, otherwise only LANGUAGE_NONE will be returned. Since the
 * default value for a 'translatable' entity property is FALSE, we ensure that
 * only entities that are able to handle translations actually get translatable
 * fields.
 *
 * @param $entity_type
 *   The type of the entity the field is attached to, e.g. 'node' or 'user'.
 * @param $field
 *   A field structure.
 * @param $suggested_languages
 *   An array of language preferences which will be intersected with the enabled
 *   languages.
 * @return
 *   An array of valid language codes.
 */
function field_multilingual_available_languages($entity_type, $field, $suggested_languages = NULL) {
  $field_languages = &drupal_static(__FUNCTION__, array());
  $field_name = $field['field_name'];

  if (!isset($field_languages[$field_name]) || !empty($suggested_languages)) {
    $translation_handlers = field_multilingual_check_translation_handlers($entity_type);

    if ($translation_handlers && $field['translatable']) {
      // The returned languages are a subset of the intersection of enabled ones
      // and suggested ones.
      $available_languages = field_multilingual_content_languages();
      $languages = !empty($suggested_languages) ? $available_languages = array_intersect($available_languages, $suggested_languages) : $available_languages;

      foreach (module_implements('field_languages') as $module) {
        $function = $module . '_field_languages';
        $function($entity_type, $field, $languages);
      }
      // Accept only available languages.
      $result = array_values(array_intersect($available_languages, $languages));
      // Do not cache suggested values as they might alter the general result.
      if (empty($suggested_languages)) {
        $field_languages[$field_name] = $result;
      }
    }
    else {
      $result = $field_languages[$field_name] = array(LANGUAGE_NONE);
    }
  }
  else {
    $result = $field_languages[$field_name];
  }

  return $result;
}

/**
 * Return available content languages.
 *
 * The languages that may be associated to fields include LANGUAGE_NONE.
 *
 * @return
 *   An array of language codes.
 */
function field_multilingual_content_languages() {
  return array_keys(language_list() + array(LANGUAGE_NONE => NULL));
}

/**
 * Check if a module is registered as a translation handler for a given entity.
 *
 * If no handler is passed, simply check if there is any translation handler
 * enabled for the given entity type.
 *
 * @param $entity_type
 *   The type of the entity whose fields are to be translated.
 * @param $handler
 *   The name of the handler to be checked.
 *
 * @return
 *   TRUE, if the handler is allowed to manage field translations.
 */
function field_multilingual_check_translation_handlers($entity_type, $handler = NULL) {
  $entity_info = entity_get_info($entity_type);

  if (isset($handler)) {
    return isset($entity_info['translation'][$handler]) && !empty($entity_info['translation'][$handler]);
  }
  elseif (isset($entity_info['translation'])) {
    foreach ($entity_info['translation'] as $handler_info) {
      // The translation handler must use a non-empty data structure.
      if (!empty($handler_info)) {
        return TRUE;
      }
    }
  }

  return FALSE;
}

/**
 * Helper function to ensure that a given language code is valid.
 *
 * Checks whether the given language is one of the enabled languages. Otherwise,
 * it returns the current, global language; or the site's default language, if
 * the additional parameter $default is TRUE.
 *
 * @param $langcode
 *   The language code to validate.
 * @param $default
 *   Whether to return the default language code or the current language code in
 *   case $langcode is invalid.
 * @return
 *   A valid language code.
 */
function field_multilingual_valid_language($langcode, $default = TRUE) {
  $enabled_languages = field_multilingual_content_languages();
  if (in_array($langcode, $enabled_languages)) {
    return $langcode;
  }
  global $language_content;
  $langcode = $default ? language_default('language') : $language_content->language;
  if (in_array($langcode, $enabled_languages)) {
    return $langcode;
  }
  // @todo Throw a more specific exception.
  throw new FieldException('No valid content language could be found.');
}
