<?php

/**
 * @file
 * Contains \Drupal\search\SearchExpression.
 */

namespace Drupal\search;

/**
 * Defines a search expression.
 */
class SearchExpression {

  /**
   * The search expression string
   *
   * @var string
   */
  protected $expression;

  /**
   * Constructs a SearchExpression.
   *
   * @param string $expression
   *   The search expression.
   */
  public function __construct($expression) {
    $this->expression = $expression;
  }

  /**
   * Gets the expression.
   *
   * @return string
   */
  public function getExpression() {
    return $this->expression;
  }

  /**
   * Extracts a module-specific search option from a search expression.
   *
   * Search options are added using SearchExpression::insert() and retrieved
   * using SearchExpression::extract(). They take the form option:value, and
   * are added to the ordinary keywords in the search expression.
   *
   * @param string $option
   *   The name of the option to retrieve from the search expression.
   *
   * @return string
   *   The value previously stored in the search expression for option $option,
   *   if any. Trailing spaces in values will not be included.
   */
  public function extract($option) {
    if (preg_match('/(^| )' . $option . ':([^ ]*)( |$)/i', $this->expression, $matches)) {
      return $matches[2];
    }
  }

  /**
   * Adds a module-specific search option to a search expression.
   *
   * Search options are added using SearchExpression::insert() and retrieved
   * using SearchExpression::extract(). They take the form option:value, and
   * are added to the ordinary keywords in the search expression.
   *
   * @param string $option
   *   The name of the option to add to the search expression.
   * @param string $value
   *   The value to add for the option. If present, it will replace any previous
   *   value added for the option. Cannot contain any spaces or | characters, as
   *   these are used as delimiters. If you want to add a blank value $option: to
   *   the search expression, pass in an empty string or a string that is
   *   composed of only spaces. To clear a previously-stored option without
   *   adding a replacement, pass in NULL for $value or omit.
   *
   * @return static|\Drupal\search\SearchExpression
   *   The search expression, with any previous value for this option removed, and
   *   a new $option:$value pair added if $value was provided.
   */
  public function insert($option, $value = NULL) {
    // Remove any previous values stored with $option.
    $this->expression = trim(preg_replace('/(^| )' . $option . ':[^ ]*/i', '', $this->expression));

    // Set new value, if provided.
    if (isset($value)) {
      $this->expression .= ' ' . $option . ':' . trim($value);
    }
    return $this;
  }

}
