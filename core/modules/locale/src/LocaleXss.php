<?php

namespace Drupal\locale;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;

/**
 * Provides the locale Xss checking.
 */
class LocaleXss {

  /**
   * Check that a string is safe to be added or imported as a translation.
   *
   * This test can be used to detect possibly bad translation strings. It should
   * not have any false positives. But it is only a test, not a transformation,
   * as it destroys valid HTML. We cannot reliably filter translation strings
   * on import because some strings are irreversibly corrupted. For example,
   * an &amp; in the translation would get encoded to &amp;amp; by
   * \Drupal\Component\Utility\Xss::filter() before being put in the database,
   * and thus would be displayed incorrectly.
   *
   * The allowed tag list is like \Drupal\Component\Utility\Xss::filterAdmin(),
   * but omitting div and img as not needed for translation and likely to cause
   * layout issues (div) or a possible attack vector (img).
   *
   * @param string $string
   *   The string to check.
   *
   * @return bool
   *   Whether $string is safe.
   */
  public static function stringIsSafe(string $string): bool {
    // Some strings have tokens in them. For tokens in the first part of href or
    // src HTML attributes, \Drupal\Component\Utility\Xss::filter() removes part
    // of the token, the part before the first colon.
    // \Drupal\Component\Utility\Xss::filter() assumes it could be an attempt to
    // inject javascript. When \Drupal\Component\Utility\Xss::filter() removes
    // part of tokens, it causes the string to not be translatable when it
    // should be translatable.
    // @see \Drupal\Tests\locale\Kernel\LocaleStringIsSafeTest::testLocaleStringIsSafe()
    //
    // We can recognize tokens since they are wrapped with brackets and are
    // only composed of alphanumeric characters, colon, underscore, and dashes.
    // We can be sure these strings are safe to strip out before the string is
    // checked in \Drupal\Component\Utility\Xss::filter() because no dangerous
    // javascript will match that pattern.
    //
    // Strings with tokens should not be assumed to be dangerous because even if
    // we evaluate them to be safe here, later replacing the token inside the
    // string will automatically mark it as unsafe as it is not the same string
    // anymore.
    //
    // @todo Do not strip out the token. Fix
    //   \Drupal\Component\Utility\Xss::filter() to not incorrectly alter the
    //   string. https://www.drupal.org/node/2372127
    $string = preg_replace('/\[[a-z0-9_-]+(:[a-z0-9_-]+)+\]/i', '', $string);

    return Html::decodeEntities($string) == Html::decodeEntities(Xss::filter($string, [
      'a',
      'abbr',
      'acronym',
      'address',
      'b',
      'bdo',
      'big',
      'blockquote',
      'br',
      'caption',
      'cite',
      'code',
      'col',
      'colgroup',
      'dd',
      'del',
      'dfn',
      'dl',
      'dt',
      'em',
      'h1',
      'h2',
      'h3',
      'h4',
      'h5',
      'h6',
      'hr',
      'i',
      'ins',
      'kbd',
      'li',
      'ol',
      'p',
      'pre',
      'q',
      'samp',
      'small',
      'span',
      'strong',
      'sub',
      'sup',
      'table',
      'tbody',
      'td',
      'tfoot',
      'th',
      'thead',
      'tr',
      'tt',
      'ul',
      'var',
      'wbr',
    ]));
  }

}
