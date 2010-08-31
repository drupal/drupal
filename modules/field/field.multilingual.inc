<?php
// $Id: field.multilingual.inc,v 1.13 2010/08/31 15:02:39 webchick Exp $

/**
 * @file
 * Functions implementing Field API multilingual support.
 */

/**
 * @defgroup field_language Field language API
 * @{
 * Handling of multilingual fields.
 *
 * Fields natively implement multilingual support, and all fields use the
 * following structure:
 * @code
 * $entity->{$field_name}[$langcode][$delta][$column_name]
 * @endcode
 * Every field can hold a single or multiple value for each language belonging
 * to the available languages set:
 * - For untranslatable fields this set only contains LANGUAGE_NONE.
 * - For translatable fields this set can contain any language code. By default
 *   it is the list returned by field_content_languages(), which contains all
 *   enabled languages with the addition of LANGUAGE_NONE. This default can be
 *   altered by modules implementing hook_field_available_languages_alter().
 *
 * The available languages for a particular field are returned by
 * field_available_languages(). Whether a field is translatable is determined by
 * calling field_is_translatable(), which checks the $field['translatable']
 * property returned by field_info_field(), and whether there is at least one
 * translation handler available for the field. A translation handler is a
 * module registering itself via hook_entity_info() to handle field
 * translations.
 *
 * By default, _field_invoke() and _field_invoke_multiple() are processing a
 * field in all available languages, unless they are given a language
 * suggestion. Based on that suggestion, _field_language_suggestion() determines
 * the languages to act on.
 *
 * Most field_attach_*() functions act on all available languages, except for
 * the following:
 * - field_attach_form() only takes a single language code, specifying which
 *   language the field values will be submitted in.
 * - field_attach_view() requires the language the entity will be displayed in.
 *   Since it is unknown whether a field translation exists for the requested
 *   language, the translation handler is responsible for performing one of the
 *   following actions:
 *   - Ignore missing translations, i.e. do not show any field values for the
 *     requested language. For example, see locale_field_language_alter().
 *   - Provide a value in a different language as fallback. By default, the
 *     fallback logic is applied separately to each field to ensure that there
 *     is a value for each field to display.
 *   The field language fallback logic relies on the global language fallback
 *   configuration. Therefore, the displayed field values can be in the
 *   requested language, but may be different if no values for the requested
 *   language are available. The default language fallback rules inspect all the
 *   enabled languages ordered by their weight. This behavior can be altered or
 *   even disabled by modules implementing hook_field_language_alter(), making
 *   it possible to choose the first approach. The display language for each
 *   field is returned by field_language().
 */

/**
 * Implements hook_multilingual_settings_changed().
 */
function field_multilingual_settings_changed() {
  field_info_cache_clear();
}

/**
 * Collects the available languages for the given entity type and field.
 *
 * If the given field has language support enabled, an array of available
 * languages will be returned, otherwise only LANGUAGE_NONE will be returned.
 * Since the default value for a 'translatable' entity property is FALSE, we
 * ensure that only entities that are able to handle translations actually get
 * translatable fields.
 *
 * @param $entity_type
 *   The type of the entity the field is attached to, e.g. 'node' or 'user'.
 * @param $field
 *   A field structure.
 *
 * @return
 *   An array of valid language codes.
 */
function field_available_languages($entity_type, $field) {
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['field_languages'] = &drupal_static(__FUNCTION__);
  }
  $field_languages = &$drupal_static_fast['field_languages'];
  $field_name = $field['field_name'];

  if (!isset($field_languages[$entity_type][$field_name])) {
    // If the field has language support enabled we retrieve an (alterable) list
    // of enabled languages, otherwise we return just LANGUAGE_NONE.
    if (field_is_translatable($entity_type, $field)) {
      $languages = field_content_languages();
      // Let other modules alter the available languages.
      $context = array('entity_type' => $entity_type, 'field' => $field);
      drupal_alter('field_available_languages', $languages, $context);
      $field_languages[$entity_type][$field_name] = $languages;
    }
    else {
      $field_languages[$entity_type][$field_name] = array(LANGUAGE_NONE);
    }
  }

  return $field_languages[$entity_type][$field_name];
}

/**
 * Process the given language suggestion based on the available languages.
 *
 * If a non-empty language suggestion is provided it must appear among the
 * available languages, otherwise it will be ignored.
 *
 * @param $available_languages
 *   An array of valid language codes.
 * @param $language_suggestion
 *   A language code or an array of language codes keyed by field name.
 * @param $field_name
 *   The name of the field being processed.
 *
 * @return
 *   An array of valid language codes.
 */
function _field_language_suggestion($available_languages, $language_suggestion, $field_name) {
  // Handle possible language suggestions.
  if (!empty($language_suggestion)) {
    // We might have an array of language suggestions keyed by field name.
    if (is_array($language_suggestion) && isset($language_suggestion[$field_name])) {
      $language_suggestion = $language_suggestion[$field_name];
    }

    // If we have a language suggestion and the suggested language is available,
    // we return only it.
    if (in_array($language_suggestion, $available_languages)) {
      $available_languages = array($language_suggestion);
    }
  }

  return $available_languages;
}

/**
 * Returns available content languages.
 *
 * The languages that may be associated to fields include LANGUAGE_NONE.
 *
 * @return
 *   An array of language codes.
 */
function field_content_languages() {
  $languages = language_list('enabled');
  return array_keys($languages[1] + array(LANGUAGE_NONE => NULL));
}

/**
 * Checks whether a field has language support.
 *
 * A field has language support enabled if its 'translatable' property is set to
 * TRUE, and its entity type has at least one translation handler registered.
 *
 * @param $entity_type
 *   The type of the entity the field is attached to.
 * @param $field
 *   A field data structure.
 *
 * @return
 *   TRUE if the field can be translated.
 */
function field_is_translatable($entity_type, $field) {
  return $field['translatable'] && field_has_translation_handler($entity_type);
}

/**
 * Checks if a module is registered as a translation handler for a given entity.
 *
 * If no handler is passed, simply check if there is any translation handler
 * enabled for the given entity type.
 *
 * @param $entity_type
 *   The type of the entity whose fields are to be translated.
 * @param $handler
 *   (optional) The name of the handler to be checked. Defaults to NULL.
 *
 * @return
 *   TRUE, if the given handler is allowed to manage field translations. If no
 *   handler is passed, TRUE means there is at least one registered translation
 *   handler.
 */
function field_has_translation_handler($entity_type, $handler = NULL) {
  $entity_info = entity_get_info($entity_type);

  if (isset($handler)) {
    return !empty($entity_info['translation'][$handler]);
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
 * Ensures that a given language code is valid.
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
function field_valid_language($langcode, $default = TRUE) {
  $enabled_languages = field_content_languages();
  if (in_array($langcode, $enabled_languages)) {
    return $langcode;
  }
  global $language_content;
  return $default ? language_default('language') : $language_content->language;
}

/**
 * Returns the display language for the fields attached to the given entity.
 *
 * The actual language for each given field is determined based on the requested
 * language and the actual data available in the fields themselves.
 * If there is no registered translation handler for the given entity type, the
 * display language to be used is just LANGUAGE_NONE, as no other language code
 * is allowed by field_available_languages().
 * If translation handlers are found, we let modules provide alternative display
 * languages for fields not having the requested language available.
 * Core language fallback rules are provided by locale_field_language_fallback()
 * which is called by locale_field_language_alter().
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entity
 *   The entity to be displayed.
 * @param $field_name
 *   (optional) The name of the field to be displayed. Defaults to NULL. If
 *   no value is specified, the display languages for every field attached to
 *   the given entity will be returned.
 * @param $langcode
 *   (optional) The language code $entity has to be displayed in. Defaults to
 *   NULL. If no value is given the current language will be used.
 *
 * @return
 *   A language code if a field name is specified, an array of language codes
 *   keyed by field name otherwise.
 */
function field_language($entity_type, $entity, $field_name = NULL, $langcode = NULL) {
  $display_languages = &drupal_static(__FUNCTION__, array());
  list($id, , $bundle) = entity_extract_ids($entity_type, $entity);
  $langcode = field_valid_language($langcode, FALSE);

  if (!isset($display_languages[$entity_type][$id][$langcode])) {
    $display_language = array();

    // By default display language is set to LANGUAGE_NONE if the field
    // translation is not available. It is up to translation handlers to
    // implement language fallback rules.
    foreach (field_info_instances($entity_type, $bundle) as $instance) {
      $display_language[$instance['field_name']] = isset($entity->{$instance['field_name']}[$langcode]) ? $langcode : LANGUAGE_NONE;
    }

    if (field_has_translation_handler($entity_type)) {
      $context = array(
        'entity_type' => $entity_type,
        'entity' => $entity,
        'language' => $langcode,
      );
      drupal_alter('field_language', $display_language, $context);
    }

    $display_languages[$entity_type][$id][$langcode] = $display_language;
  }

  $display_language = $display_languages[$entity_type][$id][$langcode];

  // Single-field mode.
  if (isset($field_name)) {
    return isset($display_language[$field_name]) ? $display_language[$field_name] : FALSE;
  }

  return $display_language;
}
