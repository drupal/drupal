<?php

/**
 * @file
 * Definition of Drupal\search\ViewsSearchQuery.
 */

namespace Drupal\search;

use Drupal\Core\Database\Query\Condition;

/**
 * Extends the core SearchQuery to be able to gets its protected values.
 */
class ViewsSearchQuery extends SearchQuery {

  /**
   * Returns the conditions property.
   *
   * @return array
   *   The query conditions.
   */
  public function &conditions() {
    return $this->conditions;
  }

  /**
   * Returns the words property.
   *
   * @return array
   *   The positive search keywords.
   */
  public function words() {
    return $this->words;
  }

  /**
   * Returns the simple property.
   *
   * @return bool
   *   TRUE if it is a simple query, and FALSE if it is complicated (phrases
   *   or LIKE).
   */
  public function simple() {
    return $this->simple;
  }

  /**
   * Returns the matches property.
   *
   * @return int
   *   The number of matches needed.
   */
  public function matches() {
    return $this->matches;
  }

  /**
   * Executes and returns the protected parseSearchExpression method.
   */
  public function publicParseSearchExpression() {
    return $this->parseSearchExpression();
  }

  /**
   * Replaces the original condition with a custom one from views recursively.
   *
   * @param string $search
   *   The searched value.
   * @param string $replace
   *   The value which replaces the search value.
   * @param \Drupal\Core\Database\Query\Condition $condition
   *   The query condition in which the string is replaced.
   */
  function conditionReplaceString($search, $replace, &$condition) {
    if ($condition['field'] instanceof Condition) {
      $conditions =& $condition['field']->conditions();
      foreach ($conditions as $key => &$subcondition) {
        if (is_numeric($key)) {
          // As conditions can have subconditions, for example db_or(), the
          // function has to be called recursively.
          $this->conditionReplaceString($search, $replace, $subcondition);
        }
      }
    }
    else {
      $condition['field'] = str_replace($search, $replace, $condition['field']);
    }
  }

}
