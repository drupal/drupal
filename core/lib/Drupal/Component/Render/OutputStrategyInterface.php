<?php

namespace Drupal\Component\Render;

/**
 * Provides an output strategy that formats HTML strings for a given context.
 *
 * Output strategies assist in transforming HTML strings into strings that are
 * appropriate for a given context (e.g. plain-text), through performing the
 * relevant formatting. No sanitization is applied.
 */
interface OutputStrategyInterface {

  /**
   * Transforms a given HTML string into to a context-appropriate output string.
   *
   * This transformation consists of performing the formatting appropriate to
   * a given output context (e.g., plain-text email subjects, HTML attribute
   * values).
   *
   * @param string|object $string
   *   An HTML string or an object with a ::__toString() magic method returning
   *   HTML markup. The source HTML markup is considered ready for output into
   *   HTML fragments and thus already properly escaped and sanitized.
   *
   * @return string
   *   A new string that is formatted according to the output strategy.
   */
  public static function renderFromHtml($string);

}
