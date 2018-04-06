<?php

namespace Drupal\Component\Utility;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\MarkupInterface;

/**
 * Contains deprecated functionality related to sanitization of markup.
 *
 * @deprecated Will be removed before Drupal 9.0.0. Use the appropriate
 *   @link sanitization sanitization functions @endlink or the @link theme_render theme and render systems @endlink
 *   so that the output can can be themed, escaped, and altered properly.
 *
 * @see https://www.drupal.org/node/2549395
 *
 * @see TwigExtension::escapeFilter()
 * @see twig_render_template()
 * @see sanitization
 * @see theme_render
 */
class SafeMarkup {

  /**
   * Checks if a string is safe to output.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $string
   *   The content to be checked.
   * @param string $strategy
   *   (optional) This value is ignored.
   *
   * @return bool
   *   TRUE if the string has been marked secure, FALSE otherwise.
   *
   * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
   *   Instead, you should just check if a variable is an instance of
   *   \Drupal\Component\Render\MarkupInterface.
   *
   * @see https://www.drupal.org/node/2549395
   */
  public static function isSafe($string, $strategy = 'html') {
    @trigger_error('SafeMarkup::isSafe() is scheduled for removal in Drupal 9.0.0. Instead, you should just check if a variable is an instance of \Drupal\Component\Render\MarkupInterface. See https://www.drupal.org/node/2549395.', E_USER_DEPRECATED);
    return $string instanceof MarkupInterface;
  }

  /**
   * Encodes special characters in a plain-text string for display as HTML.
   *
   * Also validates strings as UTF-8. All processed strings are also
   * automatically flagged as safe markup strings for rendering.
   *
   * @param string $text
   *   The text to be checked or processed.
   *
   * @return \Drupal\Component\Render\HtmlEscapedText
   *   An HtmlEscapedText object that escapes when rendered to string.
   *
   * @deprecated Will be removed before Drupal 9.0.0. Rely on Twig's
   *   auto-escaping feature, or use the @link theme_render #plain_text @endlink
   *   key when constructing a render array that contains plain text in order to
   *   use the renderer's auto-escaping feature. If neither of these are
   *   possible, \Drupal\Component\Utility\Html::escape() can be used in places
   *   where explicit escaping is needed.
   *
   * @see https://www.drupal.org/node/2549395
   * @see drupal_validate_utf8()
   */
  public static function checkPlain($text) {
    @trigger_error('SafeMarkup::checkPlain() is scheduled for removal in Drupal 9.0.0. Rely on Twig\'s auto-escaping feature, or use the @link theme_render #plain_text @endlink key when constructing a render array that contains plain text in order to use the renderer\'s auto-escaping feature. If neither of these are possible, \Drupal\Component\Utility\Html::escape() can be used in places where explicit escaping is needed. See https://www.drupal.org/node/2549395.', E_USER_DEPRECATED);
    return new HtmlEscapedText($text);
  }

  /**
   * Formats a string for HTML display by replacing variable placeholders.
   *
   * @param string $string
   *   A string containing placeholders. The string itself will not be escaped,
   *   any unsafe content must be in $args and inserted via placeholders.
   * @param array $args
   *   An array with placeholder replacements, keyed by placeholder. See
   *   \Drupal\Component\Render\FormattableMarkup::placeholderFormat() for
   *   additional information about placeholders.
   *
   * @return string|\Drupal\Component\Render\MarkupInterface
   *   The formatted string, which is an instance of MarkupInterface unless
   *   sanitization of an unsafe argument was suppressed (see above).
   *
   * @see \Drupal\Component\Render\FormattableMarkup::placeholderFormat()
   * @see \Drupal\Component\Render\FormattableMarkup
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Component\Render\FormattableMarkup.
   *
   * @see https://www.drupal.org/node/2549395
   */
  public static function format($string, array $args) {
    return new FormattableMarkup($string, $args);
  }

}
