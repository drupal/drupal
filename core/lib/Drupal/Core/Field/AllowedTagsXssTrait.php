<?php

namespace Drupal\Core\Field;

/**
 * Useful methods when dealing with displaying allowed tags.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\Field\FieldFilteredMarkup instead.
 *
 * @see \Drupal\Core\Field\FieldFilteredMarkup
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
   * \Drupal\Component\Utility\Html::escape() is not acceptable).
   *
   * @param string $string
   *   The string with raw HTML in it.
   *
   * @return \Drupal\Core\Field\FieldFilteredMarkup
   *   An XSS safe version of $string, or an empty string if $string is not
   *   valid UTF-8.
   */
  public function fieldFilterXss($string) {
    return FieldFilteredMarkup::create($string);
  }

  /**
   * Returns a list of tags allowed by AllowedTagsXssTrait::fieldFilterXss().
   */
  public function allowedTags() {
    return FieldFilteredMarkup::allowedTags();
  }

  /**
   * Returns a human-readable list of allowed tags for display in help texts.
   */
  public function displayAllowedTags() {
    return FieldFilteredMarkup::displayAllowedTags();
  }

}
