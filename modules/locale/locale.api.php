<?php
// $Id$

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
 * Perform alterations on translation links.
 *
 * A translation link may need to point to a different path or use a translated
 * link text before going through l(), which will just handle the path aliases.
 *
 * @param $links
 *   Nested array of links keyed by language code.
 * @param $path
 *   The current path.
 */
function hook_translation_link_alter(array &$links, $path) {
  global $language;

  if (isset($links[$language])) {
    foreach ($links[$language] as $link) {
      $link['attributes']['class'] .= ' active-language';
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
