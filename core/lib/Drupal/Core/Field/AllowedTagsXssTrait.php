<?php
/**
 * @file
 * Contains \Drupal\Core\Field\AllowedTagsXssTrait.
 */

namespace Drupal\Core\Field;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;

/**
 * Useful methods when dealing with displaying allowed tags.
 */
trait AllowedTagsXssTrait {

  /**
   * Filters an HTML string to prevent XSS vulnerabilities.
   *
   * Like \Drupal\Component\Utility\Xss::filterAdmin(), but with a shorter list
   * of allowed tags.
   *
   * Used for items entered by administrators, like field descriptions, allowed
   * values, where some (mainly inline) mark-up may be desired (so
   * \Drupal\Component\Utility\SafeMarkup::checkPlain() is not acceptable).
   *
   * @param string $string
   *   The string with raw HTML in it.
   *
   * @return \Drupal\Component\Utility\SafeMarkup
   *   An XSS safe version of $string, or an empty string if $string is not
   *   valid UTF-8.
   */
  public function fieldFilterXss($string) {
    // All known XSS vectors are filtered out by
    // \Drupal\Component\Utility\Xss::filter(), all tags in the markup are
    // allowed intentionally by the trait, and no danger is added in by
    // \Drupal\Component\Utility\HTML::normalize(). Since the normalized value
    // is essentially the same markup, designate this string as safe as well.
    // This method is an internal part of field sanitization, so the resultant,
    // sanitized string should be printable as is.
    //
    // @todo Free this memory in https://www.drupal.org/node/2505963.
    return SafeMarkup::set(Html::normalize(Xss::filter($string, $this->allowedTags())));
  }

  /**
   * Returns a list of tags allowed by AllowedTagsXssTrait::fieldFilterXss().
   */
  public function allowedTags() {
    return array('a', 'b', 'big', 'code', 'del', 'em', 'i', 'ins',  'pre', 'q', 'small', 'span', 'strong', 'sub', 'sup', 'tt', 'ol', 'ul', 'li', 'p', 'br', 'img');
  }

  /**
   * Returns a human-readable list of allowed tags for display in help texts.
   */
  public function displayAllowedTags() {
    return '<' . implode('> <', $this->allowedTags()) . '>';
  }

}
