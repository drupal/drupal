<?php

/**
 * @file
 * Hooks provided by the Language module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * React to a language about to be added or updated in the system.
 *
 * @param $language
 *   A language object.
 */
function hook_language_presave($language) {
  if ($language->default) {
    // React to a new default language.
    example_new_default_language($language);
  }
}

/**
 * React to a language that was just added to the system.
 *
 * @param $language
 *   A language object.
 */
function hook_language_insert($language) {
  example_refresh_permissions();
}

/**
 * React to a language that was just updated in the system.
 *
 * @param $language
 *   A language object.
 */
function hook_language_update($language) {
  example_refresh_permissions();
}

/**
 * Allow modules to react before the deletion of a language.
 *
 * @param $language
 *   The language object of the language that is about to be deleted.
 */
function hook_language_delete($language) {
  // On nodes with this language, unset the language
  db_update('node')
    ->fields(array('language' => ''))
    ->condition('language', $language->id)
    ->execute();
}

/**
 * Allow modules to alter the language fallback candidates.
 *
 * @param array $candidates
 *   An array of language codes whose order will determine the language fallback
 *   order.
 * @param array $context
 *   A language fallback context.
 *
 * @see \Drupal\Core\Language\LanguageManager::getFallbackCandidates()
 */
function hook_language_fallback_candidates_alter(array &$candidates, array $context) {
  $candidates = array_reverse($candidates);
}

/**
 * Allow modules to alter the fallback candidates for specific operations.
 *
 * @param array $candidates
 *   An array of language codes whose order will determine the language fallback
 *   order.
 * @param array $context
 *   A language fallback context.
 *
 * @see \Drupal\Core\Language\LanguageManager::getFallbackCandidates()
 */
function hook_language_fallback_candidates_OPERATION_alter(array &$candidates, array $context) {
  // We know that the current OPERATION deals with entities so no need to check
  // here.
  if ($context['data']->entityType() == 'node') {
    $candidates = array_reverse($candidates);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
