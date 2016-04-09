<?php

namespace Drupal\search\Plugin\views\field;

use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Field handler for search score.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("search_score")
 */
class Score extends NumericField {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Check to see if the search filter added 'score' to the table.
    // Our filter stores it as $handler->search_score -- and we also
    // need to check its relationship to make sure that we're using the same
    // one or obviously this won't work.
    foreach ($this->view->filter as $handler) {
      if (isset($handler->search_score) && ($handler->relationship == $this->relationship)) {
        $this->field_alias = $handler->search_score;
        $this->tableAlias = $handler->tableAlias;
        return;
      }
    }

    // Hide this field if no search filter is in place.
    $this->options['exclude'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Only render if we exist.
    if (isset($this->tableAlias)) {
      return parent::render($values);
    }
  }
}
