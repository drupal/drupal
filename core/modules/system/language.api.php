<?php

/**
 * @file
 * Hooks provided by the base system for language support.
 */

/**
 * @addtogroup hooks
 * @{
 */

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
  $language_interface = \Drupal::languageManager()->getCurrentLanguage();

  if ($type == \Drupal\Core\Language\Language::TYPE_CONTENT && isset($links[$language_interface->id])) {
    foreach ($links[$language_interface->id] as $link) {
      $link['attributes']['class'][] = 'active-language';
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */

/**
 * @defgroup transliteration Transliteration
 * @{
 * Transliterate from Unicode to US-ASCII
 *
 * Transliteration is the process of translating individual non-US-ASCII
 * characters into ASCII characters, which specifically does not transform
 * non-printable and punctuation characters in any way. This process will always
 * be both inexact and language-dependent. For instance, the character Ö (O with
 * an umlaut) is commonly transliterated as O, but in German text, the
 * convention would be to transliterate it as Oe or OE, depending on the context
 * (beginning of a capitalized word, or in an all-capital letter context).
 *
 * The Drupal default transliteration process transliterates text character by
 * character using a database of generic character transliterations and
 * language-specific overrides. Character context (such as all-capitals
 * vs. initial capital letter only) is not taken into account, and in
 * transliterations of capital letters that result in two or more letters, by
 * convention only the first is capitalized in the Drupal transliteration
 * result. Also, only Unicode characters of 4 bytes or less can be
 * transliterated in the base system; language-specific overrides can be made
 * for longer Unicode characters. So, the process has limitations; however,
 * since the reason for transliteration is typically to create machine names or
 * file names, this should not really be a problem. After transliteration,
 * other transformation or validation may be necessary, such as converting
 * spaces to another character, removing non-printable characters,
 * lower-casing, etc.
 *
 * Here is a code snippet to transliterate some text:
 * @code
 * // Use the current default interface language.
 * $langcode = \Drupal::languageManager()->getCurrentLanguage()->id;
 * // Instantiate the transliteration class.
 * $trans = \Drupal::transliteration();
 * // Use this to transliterate some text.
 * $transformed = $trans->transliterate($string, $langcode);
 * @endcode
 *
 * Drupal Core provides the generic transliteration character tables and
 * overrides for a few common languages; modules can implement
 * hook_transliteration_overrides_alter() to provide further language-specific
 * overrides (including providing transliteration for Unicode characters that
 * are longer than 4 bytes). Modules can also completely override the
 * transliteration classes in \Drupal\Core\CoreServiceProvider.
 */

/**
 * Provide language-specific overrides for transliteration.
 *
 * If the overrides you want to provide are standard for your language, consider
 * providing a patch for the Drupal Core transliteration system instead of using
 * this hook. This hook can be used temporarily until Drupal Core's
 * transliteration tables are fixed, or for sites that want to use a
 * non-standard transliteration system.
 *
 * @param array $overrides
 *   Associative array of language-specific overrides whose keys are integer
 *   Unicode character codes, and whose values are the transliterations of those
 *   characters in the given language, to override default transliterations.
 * @param string $langcode
 *   The code for the language that is being transliterated.
 *
 * @ingroup hooks
 */
function hook_transliteration_overrides_alter(&$overrides, $langcode) {
  // Provide special overrides for German for a custom site.
  if ($langcode == 'de') {
    // The core-provided transliteration of Ä is Ae, but we want just A.
    $overrides[0xC4] = 'A';
  }
}

/**
 * @} End of "defgroup transliteration".
 */
