<?php

/**
 * @file
 * Hooks provided by the Locale module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows modules to define their own text groups that can be translated.
 *
 * @param $op
 *   Type of operation. Currently, only supports 'groups'.
 */
function hook_locale($op = 'groups') {
  switch ($op) {
    case 'groups':
      return array('custom' => t('Custom'));
  }
}

/**
 * Allows modules to act after language initialization has been performed.
 *
 * This is primarily needed to provide translation for configuration variables
 * in the proper bootstrap phase. Variables are user-defined strings and
 * therefore should not be translated via t(), since the source string can
 * change without notice and any previous translation would be lost. Moreover,
 * since variables can be used in the bootstrap phase, we need a bootstrap hook
 * to provide a translation early enough to avoid misalignments between code
 * using the original values and code using the translated values. However
 * modules implementing hook_boot() should be aware that language initialization
 * did not happen yet and thus they cannot rely on translated variables.
 */
function hook_language_init() {
  global $language, $conf;

  switch ($language->language) {
    case 'it':
      $conf['site_name'] = 'Il mio sito Drupal';
      break;

    case 'fr':
      $conf['site_name'] = 'Mon site Drupal';
      break;
  }
}

/**
 * Perform alterations on language switcher links.
 *
 * A language switcher link may need to point to a different path or use a
 * translated link text before going through l(), which will just handle the
 * path aliases.
 *
 * @param $links
 *   Nested array of links keyed by language code.
 * @param $type
 *   The language type the links will switch.
 * @param $path
 *   The current path.
 */
function hook_language_switch_links_alter(array &$links, $type, $path) {
  global $language;

  if ($type == LANGUAGE_TYPE_CONTENT && isset($links[$language->language])) {
    foreach ($links[$language->language] as $link) {
      $link['attributes']['class'][] = 'active-language';
    }
  }
}

/**
 * Allow modules to define their own language types.
 *
 * @return
 *   An array of language type definitions. Each language type has an identifier
 *   key. The language type definition is an associative array that may contain
 *   the following key-value pairs:
 *   - "name": The human-readable language type identifier.
 *   - "description": A description of the language type.
 *   - "fixed": An array of language provider identifiers. Defining this key
 *     makes the language type non-configurable.
 */
function hook_language_types_info() {
  return array(
    'custom_language_type' => array(
      'name' => t('Custom language'),
      'description' => t('A custom language type.'),
    ),
    'fixed_custom_language_type' => array(
      'fixed' => array('custom_language_provider'),
    ),
  );
}

/**
 * Perform alterations on language types.
 *
 * @param $language_types
 *   Array of language type definitions.
 */
function hook_language_types_info_alter(array &$language_types) {
  if (isset($language_types['custom_language_type'])) {
    $language_types['custom_language_type_custom']['description'] = t('A far better description.');
  }
}

/**
 * Allow modules to define their own language providers.
 *
 * @return
 *   An array of language provider definitions. Each language provider has an
 *   identifier key. The language provider definition is an associative array
 *   that may contain the following key-value pairs:
 *   - "types": An array of allowed language types. If a language provider does
 *     not specify which language types it should be used with, it will be
 *     available for all the configurable language types.
 *   - "callbacks": An array of functions that will be called to perform various
 *     tasks. Possible key-value pairs are:
 *     - "language": Required. The callback that will determine the language
 *       value.
 *     - "switcher": The callback that will determine the language switch links
 *       associated to the current language provider.
 *     - "url_rewrite": The callback that will provide URL rewriting.
 *   - "file": A file that will be included before the callback is invoked; this
 *     allows callback functions to be in separate files.
 *   - "weight": The default weight the language provider has.
 *   - "name": A human-readable identifier.
 *   - "description": A description of the language provider.
 *   - "config": An internal path pointing to the language provider
 *     configuration page.
 *   - "cache": The value Drupal's page cache should be set to for the current
 *     language provider to be invoked.
 */
function hook_language_negotiation_info() {
  return array(
    'custom_language_provider' => array(
      'callbacks' => array(
        'language' => 'custom_language_provider_callback',
        'switcher' => 'custom_language_switcher_callback',
        'url_rewrite' => 'custom_language_url_rewrite_callback',
      ),
      'file' => drupal_get_path('module', 'custom') . '/custom.module',
      'weight' => -4,
      'types' => array('custom_language_type'),
      'name' => t('Custom language provider'),
      'description' => t('This is a custom language provider.'),
      'cache' => 0,
    ),
  );
}

/**
 * Perform alterations on language providers.
 *
 * @param $language_providers
 *   Array of language provider definitions.
 */
function hook_language_negotiation_info_alter(array &$language_providers) {
  if (isset($language_providers['custom_language_provider'])) {
    $language_providers['custom_language_provider']['config'] = 'admin/config/regional/language/configure/custom-language-provider';
  }
}

/**
 * Allow modules to react to language settings changes.
 *
 * Every module needing to act when the number of enabled languages changes
 * should implement this. This is an "internal" hook and should not be invoked
 * elsewhere. The typical implementation would trigger some kind of rebuilding,
 * this way system components could properly react to the change of the enabled
 * languages number.
 */
function hook_multilingual_settings_changed() {
  field_info_cache_clear();
}

/**
 * Perform alterations on the language fallback candidates.
 *
 * @param $fallback_candidates
 *   An array of language codes whose order will determine the language fallback
 *   order.
 */
function hook_language_fallback_candidates_alter(array &$fallback_candidates) {
  $fallback_candidates = array_reverse($fallback_candidates);
}

/**
 * @} End of "addtogroup hooks".
 */
