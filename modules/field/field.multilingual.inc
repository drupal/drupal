<?php
// $Id$

/**
 * @file
 * Multilingual field API helper functions.
 */

/**
 * Implement hook_multilingual_settings_changed().
 */
function field_multilingual_settings_changed() {
  field_info_cache_clear();
}

/**
 * Collect the available languages for the given entity type and field.
 *
 * If an entity has a translation handler and the given field is translatable,
 * a (not necessarily strict) subset of the current enabled languages will be
 * returned, otherwise only FIELD_LANGUAGE_NONE will be returned. Since the
 * default value for a 'translatable' entity property is FALSE, we ensure that
 * only entities that are able to handle translations actually get translatable
 * fields.
 *
 * @param $obj_type
 *   The type of the entity the field is attached to, e.g. 'node' or 'user'.
 * @param $field
 *   A field structure.
 * @param $suggested_languages
 *   An array of language preferences which will be intersected with the enabled
 *   languages.
 * @return
 *   An array of valid language codes.
 */
function field_multilingual_available_languages($obj_type, $field, $suggested_languages = NULL) {
  $field_languages = &drupal_static(__FUNCTION__, array());
  $field_name = $field['field_name'];

  if (!isset($field_languages[$field_name]) || !empty($suggested_languages)) {
    $translation_handlers = field_multilingual_check_translation_handlers($obj_type);

    if ($translation_handlers && $field['translatable']) {
      // The returned languages are a subset of the intersection of enabled ones
      // and suggested ones.
      $available_languages = field_multilingual_content_languages();
      $languages = !empty($suggested_languages) ? $available_languages = array_intersect($available_languages, $suggested_languages) : $available_languages;

      foreach (module_implements('field_languages') as $module) {
        $function = $module . '_field_languages';
        $function($obj_type, $field, $languages);
      }
      // Accept only available languages.
      $result = array_values(array_intersect($available_languages, $languages));
      // Do not cache suggested values as they might alter the general result.
      if (empty($suggested_languages)) {
        $field_languages[$field_name] = $result;
      }
    }
    else {
      $result = $field_languages[$field_name] = array(FIELD_LANGUAGE_NONE);
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
 * The languages that may be associated to fields include FIELD_LANGAUGE_NONE.
 *
 * @return
 *   An array of language codes.
 */
function field_multilingual_content_languages() {
  return array_keys(language_list() + array(FIELD_LANGUAGE_NONE => NULL));
}

/**
 * Check if a module is registered as a translation handler for a given entity.
 *
 * If no handler is passed, simply check if there is any translation handler
 * enabled for the given entity type.
 *
 * @param $obj_type
 *   The type of the entity whose fields are to be translated.
 * @param $handler
 *   The name of the handler to be checked.
 *
 * @return
 *   TRUE, if the handler is allowed to manage field translations.
 */
function field_multilingual_check_translation_handlers($obj_type, $handler = NULL) {
  $obj_info = entity_get_info($obj_type);

  if (isset($handler)) {
    return isset($obj_info['translation'][$handler]) && !empty($obj_info['translation'][$handler]);
  }
  elseif (isset($obj_info['translation'])) {
    foreach ($obj_info['translation'] as $handler_info) {
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
  // @todo Currently, node language neutral code is an empty string. Node passes
  //   $node->language as language parameter to field_attach_form(). We might
  //   want to unify the two "language neutral" language codes.
  if ($langcode === '') {
    return FIELD_LANGUAGE_NONE;
  }
  global $language;
  $langcode = $default ? language_default('language') : $language->language;
  if (in_array($langcode, $enabled_languages)) {
    return $langcode;
  }
  // @todo Throw a more specific exception.
  throw new FieldException('No valid content language could be found.');
}
