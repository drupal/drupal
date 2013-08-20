<?php

/**
 * @file
 * Functions implementing Field API multilingual support.
 */

use Drupal\Core\Language\Language;
use Drupal\Core\Entity\EntityInterface;

/**
 * @defgroup field_language Field Language API
 * @{
 * Handles multilingual fields.
 *
 * Fields natively implement multilingual support, and all fields use the
 * following structure:
 * @code
 * $entity->{$field_name}[$langcode][$delta][$column_name]
 * @endcode
 * Every field can hold a single or multiple value for each language code
 * belonging to the available language codes set:
 * - For untranslatable fields this set only contains Language::LANGCODE_NOT_SPECIFIED.
 * - For translatable fields this set can contain any language code. By default
 *   it is the list returned by field_content_languages(), which contains all
 *   installed languages with the addition of Language::LANGCODE_NOT_SPECIFIED. This
 *   default can be altered by modules implementing
 *   hook_field_available_languages_alter().
 *
 * The available language codes for a particular field are returned by
 * field_available_languages(). Whether a field is translatable is determined by
 * calling field_is_translatable(), which checks the $field['translatable']
 * property returned by field_info_field() and whether the entity type the field
 * is attached to supports translation.
 *
 * By default, field_invoke_method() processes a field in all available
 * languages, unless they are given a language code suggestion. Based on that
 * suggestion, _field_language_suggestion() determines the languages to act on.
 *
 * Most field_attach_*() functions act on all available language codes, except
 * for the following:
 * - field_attach_form() only takes a single language code, specifying which
 *   language the field values will be submitted in.
 * - field_attach_view() requires the language the entity will be displayed in.
 *   Since it is unknown whether a field translation exists for the requested
 *   language, the translation handler is responsible for performing one of the
 *   following actions:
 *   - Ignore missing translations, i.e. do not show any field values for the
 *     requested language. For example, see field_field_language_alter().
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
 *
 * See @link field Field API @endlink for information about the other parts of
 * the Field API.
 */


/**
 * Applies language fallback rules to the fields attached to the given entity.
 *
 * Core language fallback rules simply check if fields have a field translation
 * for the requested language code. If so, the requested language is returned,
 * otherwise all the fallback candidates are inspected to see if there is a
 * field translation available in another language.
 * By default this is called by field_field_language_alter(), but this
 * behavior can be disabled by setting the 'field.settings.language_fallback'
 * variable to FALSE.
 *
 * @param $field_langcodes
 *   A reference to an array of language codes keyed by field name.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity to be displayed.
 * @param $langcode
 *   The language code $entity has to be displayed in.
 */
function field_language_fallback(&$field_langcodes, EntityInterface $entity, $langcode) {
  // Lazily init fallback candidates to avoid unnecessary calls.
  $fallback_candidates = NULL;

  foreach ($field_langcodes as $field_name => $field_langcode) {
    // If the requested language is defined for the current field use it,
    // otherwise search for a fallback value among the fallback candidates.
    if (isset($entity->{$field_name}[$langcode])) {
      $field_langcodes[$field_name] = $langcode;
    }
    elseif (!empty($entity->{$field_name})) {
      if (!isset($fallback_candidates)) {
        require_once DRUPAL_ROOT . '/core/includes/language.inc';
        $fallback_candidates = language_fallback_get_candidates();
      }
      foreach ($fallback_candidates as $fallback_langcode) {
        if (isset($entity->{$field_name}[$fallback_langcode])) {
          $field_langcodes[$field_name] = $fallback_langcode;
          break;
        }
      }
    }
  }
}

/**
 * Collects the available language codes for the given entity type and field.
 *
 * If the given field has language support enabled, an array of available
 * language codes will be returned, otherwise only Language::LANGCODE_NOT_SPECIFIED will
 * be returned. Since the default value for a 'translatable' entity property is
 * FALSE, we ensure that only entities that are able to handle translations
 * actually get translatable fields.
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
    $drupal_static_fast['field_langcodes'] = &drupal_static(__FUNCTION__);
  }
  $field_langcodes = &$drupal_static_fast['field_langcodes'];
  $field_name = $field['field_name'];

  if (!isset($field_langcodes[$entity_type][$field_name])) {
    // If the field has language support enabled we retrieve an (alterable) list
    // of enabled languages, otherwise we return just Language::LANGCODE_NOT_SPECIFIED.
    if (field_is_translatable($entity_type, $field)) {
      $langcodes = field_content_languages();
      // Let other modules alter the available languages.
      $context = array('entity_type' => $entity_type, 'field' => $field);
      drupal_alter('field_available_languages', $langcodes, $context);
      $field_langcodes[$entity_type][$field_name] = $langcodes;
    }
    else {
      $field_langcodes[$entity_type][$field_name] = array(Language::LANGCODE_NOT_SPECIFIED);
    }
  }

  return $field_langcodes[$entity_type][$field_name];
}

/**
 * Process the language code suggestion based on the available language codes.
 *
 * If a non-empty language code suggestion is provided it must appear among the
 * available language codes, otherwise it will be ignored.
 *
 * @param $available_langcodes
 *   An array of valid language codes.
 * @param $langcode_suggestion
 *   A language code or an array of language codes keyed by field name.
 * @param $field_name
 *   The name of the field being processed.
 *
 * @return
 *   An array of valid language codes.
 */
function _field_language_suggestion($available_langcodes, $langcode_suggestion, $field_name) {
  // Handle possible language suggestions.
  if (!empty($langcode_suggestion)) {
    // We might have an array of language suggestions keyed by field name.
    if (is_array($langcode_suggestion) && isset($langcode_suggestion[$field_name])) {
      $langcode_suggestion = $langcode_suggestion[$field_name];
    }

    // If we have a single language code suggestion and it is available, we just
    // return it.
    if (in_array($langcode_suggestion, $available_langcodes)) {
      $available_langcodes = array($langcode_suggestion);
    }
  }

  return $available_langcodes;
}

/**
 * Returns available content language codes.
 *
 * @return
 *   An array of language codes.
 */
function field_content_languages() {
  return array_keys(language_list(Language::STATE_ALL));
}

/**
 * Checks whether field language fallback is enabled.
 */
function field_language_fallback_enabled() {
  return language_multilingual() && Drupal::config('field.settings')->get('language_fallback');
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
 * If no handler is passed, the function simply checks if there is any
 * translation handler enabled for the given entity type.
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
 *
 * @todo Remove this once the migration to the Entity Field API is complete.
 */
function field_has_translation_handler($entity_type, $handler = NULL) {
  $info = entity_get_info($entity_type);
  return !empty($info['translatable']);
}

/**
 * Ensures that a given language code is valid.
 *
 * Checks whether the given language code is one of the enabled language codes.
 * Otherwise, it returns the current, global language code; or the site's
 * default language code, if the additional parameter $default is TRUE.
 *
 * @param $langcode
 *   The language code to validate.
 * @param $default
 *   Whether to return the default language code or the current language code in
 *   case $langcode is invalid.
 *
 * @return
 *   A valid language code.
 */
function field_valid_language($langcode, $default = TRUE) {
  $languages = field_content_languages();
  if (in_array($langcode, $languages)) {
    return $langcode;
  }
  return $default ? language_default()->id : language(Language::TYPE_CONTENT)->id;
}

/**
 * Returns the display language code for the fields attached to the given
 * entity.
 *
 * The actual language code for each given field is determined based on the
 * requested language code and the actual data available in the fields
 * themselves.
 * If there is no registered translation handler for the given entity type, the
 * display language code to be used is just Language::LANGCODE_NOT_SPECIFIED, as no other
 * language code is allowed by field_available_languages().
 *
 * If translation handlers are found, we let modules provide alternative display
 * language codes for fields not having the requested language code available.
 * Core language fallback rules are provided by field_language_fallback()
 * which is called by field_field_language_alter().
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity to be displayed.
 * @param $field_name
 *   (optional) The name of the field to be displayed. Defaults to NULL. If
 *   no value is specified, the display language codes for every field attached
 *   to the given entity will be returned.
 * @param $langcode
 *   (optional) The language code $entity has to be displayed in. Defaults to
 *   NULL. If no value is given the current language will be used.
 *
 * @return
 *   A language code if a field name is specified, an array of language codes
 *   keyed by field name otherwise.
 */
function field_language(EntityInterface $entity, $field_name = NULL, $langcode = NULL) {
  $display_langcodes = &drupal_static(__FUNCTION__, array());
  $id = $entity->id();
  $bundle = $entity->bundle();
  $entity_type = $entity->entityType();
  $langcode = field_valid_language($langcode, FALSE);
  if (!isset($display_langcodes[$entity_type][$id][$langcode])) {
    $display_langcode = array();

    // By default, display language is set to one of the locked languages
    // if the field translation is not available. It is up to translation
    // handlers to implement language fallback rules.
    foreach (field_info_instances($entity_type, $bundle) as $instance) {
      if (isset($entity->{$instance['field_name']}[$langcode])) {
        $display_langcode[$instance['field_name']] = $langcode;
      }
      else {
        // If the field has a value for one of the locked languages, then use
        // that language for display. If not, the default one will be
        // Language::LANGCODE_NOT_SPECIFIED.
        $display_langcode[$instance['field_name']] = Language::LANGCODE_NOT_SPECIFIED;
        foreach (language_list(Language::STATE_LOCKED) as $language_locked) {
          if (isset($entity->{$instance['field_name']}[$language_locked->id])) {
            $display_langcode[$instance['field_name']] = $language_locked->id;
            break;
          }
        }
      }
    }

    if (field_has_translation_handler($entity_type)) {
      $context = array(
        'entity' => $entity,
        'langcode' => $langcode,
      );
      // Do not apply core language fallback rules if they are disabled or if
      // the entity does not have a translation handler registered.
      if (field_language_fallback_enabled() && field_has_translation_handler($entity_type)) {
        field_language_fallback($display_langcode, $context['entity'], $context['langcode']);
      }
      drupal_alter('field_language', $display_langcode, $context);
    }

    $display_langcodes[$entity_type][$id][$langcode] = $display_langcode;
  }

  $display_langcode = $display_langcodes[$entity_type][$id][$langcode];

  // Single-field mode.
  if (isset($field_name)) {
    return isset($display_langcode[$field_name]) ? $display_langcode[$field_name] : FALSE;
  }

  return $display_langcode;
}
