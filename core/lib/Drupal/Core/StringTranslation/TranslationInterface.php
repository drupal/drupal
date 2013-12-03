<?php

/**
 * @file
 * Contains \Drupal\Core\StringTranslation\TranslationInterface.
 */

namespace Drupal\Core\StringTranslation;

interface TranslationInterface {

  /**
   * Translates a string to the current language or to a given language.
   *
   * @param string $string
   *   A string containing the English string to translate.
   * @param array $args
   *   An associative array of replacements to make after translation. Based
   *   on the first character of the key, the value is escaped and/or themed.
   *   See \Drupal\Component\Utility\String::format() for details.
   * @param array $options
   *   An associative array of additional options, with the following elements:
   *   - 'langcode': The language code to translate to a language other than
   *      what is used to display the page.
   *   - 'context': The context the source string belongs to.
   *
   * @return string
   *   The translated string.
   *
   * @see \Drupal\Component\Utility\String::format()
   */
  public function translate($string, array $args = array(), array $options = array());

  /**
   * Formats a string containing a count of items.
   *
   * This function ensures that the string is pluralized correctly. Since t() is
   * called by this function, make sure not to pass already-localized strings to
   * it.
   *
   * For example:
   * @code
   *   $output = $string_translation->formatPlural($node->comment_count, '1 comment', '@count comments');
   * @endcode
   *
   * Example with additional replacements:
   * @code
   *   $output = $string_translation->formatPlural($update_count,
   *     'Changed the content type of 1 post from %old-type to %new-type.',
   *     'Changed the content type of @count posts from %old-type to %new-type.',
   *     array('%old-type' => $info->old_type, '%new-type' => $info->new_type));
   * @endcode
   *
   * @param int $count
   *   The item count to display.
   * @param string $singular
   *   The string for the singular case. Make sure it is clear this is singular,
   *   to ease translation (e.g. use "1 new comment" instead of "1 new"). Do not
   *   use @count in the singular string.
   * @param string $plural
   *   The string for the plural case. Make sure it is clear this is plural, to
   *   ease translation. Use @count in place of the item count, as in
   *   "@count new comments".
   * @param array $args
   *   An associative array of replacements to make after translation. Instances
   *   of any key in this array are replaced with the corresponding value.
   *   Based on the first character of the key, the value is escaped and/or
   *   themed. See format_string(). Note that you do not need to include @count
   *   in this array; this replacement is done automatically for the plural case.
   * @param array $options
   *   An associative array of additional options. See t() for allowed keys.
   *
   * @return string
   *   A translated string.
   *
   * @see self::translate
   * @see \Drupal\Component\Utility\String
   * @see t()
   * @see format_string()
   */
  public function formatPlural($count, $singular, $plural, array $args = array(), array $options = array());

}
