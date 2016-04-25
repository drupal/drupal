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
 * Define language types.
 *
 * @return array
 *   An associative array of language type definitions. The keys are the
 *   identifiers, which are also used as names for global variables representing
 *   the types in the bootstrap phase. The values are associative arrays that
 *   may contain the following elements:
 *   - name: The human-readable language type identifier.
 *   - description: A description of the language type.
 *   - locked: A boolean indicating if the user can choose whether to configure
 *     the language type or not using the UI.
 *   - fixed: A fixed array of language negotiation method identifiers to use to
 *     initialize this language. If locked is set to TRUE and fixed is set, it
 *     will always use the specified methods in the given priority order. If not
 *     present and locked is TRUE then language-interface will be
 *     used.
 *
 *  @todo Rename the 'fixed' key to something more meaningful, for instance
 *     'negotiation settings'. See https://www.drupal.org/node/2166879.
 *
 * @see hook_language_types_info_alter()
 * @ingroup language_negotiation
 */
function hook_language_types_info() {
  return array(
    'custom_language_type' => array(
      'name' => t('Custom language'),
      'description' => t('A custom language type.'),
      'locked' => FALSE,
    ),
    'fixed_custom_language_type' => array(
      'locked' => TRUE,
      'fixed' => array('custom_language_negotiation_method'),
    ),
  );
}

/**
 * Perform alterations on language types.
 *
 * @param array $language_types
 *   Array of language type definitions.
 *
 * @see hook_language_types_info()
 * @ingroup language_negotiation
 */
function hook_language_types_info_alter(array &$language_types) {
  if (isset($language_types['custom_language_type'])) {
    $language_types['custom_language_type_custom']['description'] = t('A far better description.');
  }
}

/**
 * Perform alterations on language negotiation methods.
 *
 * @param array $negotiation_info
 *   Array of language negotiation method definitions.
 *
 * @ingroup language_negotiation
 */
function hook_language_negotiation_info_alter(array &$negotiation_info) {
  if (isset($negotiation_info['custom_language_method'])) {
    $negotiation_info['custom_language_method']['config'] = 'admin/config/regional/language/detection/custom-language-method';
  }
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
 * @see \Drupal\Core\Language\LanguageManagerInterface::getFallbackCandidates()
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
 * @see \Drupal\Core\Language\LanguageManagerInterface::getFallbackCandidates()
 */
function hook_language_fallback_candidates_OPERATION_alter(array &$candidates, array $context) {
  // We know that the current OPERATION deals with entities so no need to check
  // here.
  if ($context['data']->getEntityTypeId() == 'node') {
    $candidates = array_reverse($candidates);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
