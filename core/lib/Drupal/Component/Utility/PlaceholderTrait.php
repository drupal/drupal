<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\PlaceholderTrait.
 */

namespace Drupal\Component\Utility;

/**
 * Offers functionality for formatting strings using placeholders.
 */
trait PlaceholderTrait {

  /**
   * Formats a string by replacing variable placeholders.
   *
   * @param string $string
   *   A string containing placeholders.
   * @param array $args
   *   An associative array of replacements to make.
   * @param bool &$safe
   *   A boolean indicating whether the string is safe or not (optional).
   *
   * @return string
   *   The string with the placeholders replaced.
   *
   * @see \Drupal\Component\Utility\SafeMarkup::format()
   * @see \Drupal\Core\StringTranslation\TranslatableString::render()
   */
  protected static function placeholderFormat($string, array $args, &$safe = TRUE) {
    // Transform arguments before inserting them.
    foreach ($args as $key => $value) {
      switch ($key[0]) {
        case '@':
          // Escaped only.
          if (!SafeMarkup::isSafe($value)) {
            $args[$key] = Html::escape($value);
          }
          break;

        case '%':
        default:
          // Escaped and placeholder.
          if (!SafeMarkup::isSafe($value)) {
            $value = Html::escape($value);
          }
          $args[$key] = '<em class="placeholder">' . $value . '</em>';
          break;

        case '!':
          // Pass-through.
          if (!SafeMarkup::isSafe($value)) {
            $safe = FALSE;
          }
      }
    }
    return strtr($string, $args);
  }

}
