<?php
/**
 * @file
 * Contains \Drupal\Component\Utility\PlainTextOutput.
 */

namespace Drupal\Component\Utility;

/**
 * Provides an output strategy for transforming HTML into simple plain text.
 *
 * Use this when rendering a given HTML string into a plain text string that
 * does not need special formatting, such as a label or an email subject.
 *
 * Returns a string with HTML tags stripped and HTML entities decoded suitable
 * for email or other non-HTML contexts.
 */
class PlainTextOutput implements OutputStrategyInterface {

  /**
   * {@inheritdoc}
   */
  public static function renderFromHtml($string) {
    return Html::decodeEntities(strip_tags((string) $string));
  }

}
