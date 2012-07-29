<?php

namespace Drupal\search;

/**
 * Extends the core SearchQuery.
 *
 * @todo: Make this class PSR-0 compatible.
 */
class ViewsSearchQuery extends SearchQuery {
  public function &conditions() {
    return $this->conditions;
  }
  public function words() {
    return $this->words;
  }

  public function simple() {
    return $this->simple;
  }

  public function matches() {
    return $this->matches;
  }

  public function publicParseSearchExpression() {
    return $this->parseSearchExpression();
  }

  function condition_replace_string($search, $replace, &$condition) {
    if ($condition['field'] instanceof DatabaseCondition) {
      $conditions =& $condition['field']->conditions();
      foreach ($conditions as $key => &$subcondition) {
        if (is_numeric($key)) {
          $this->condition_replace_string($search, $replace, $subcondition);
        }
      }
    }
    else {
      $condition['field'] = str_replace($search, $replace, $condition['field']);
    }
  }
}
