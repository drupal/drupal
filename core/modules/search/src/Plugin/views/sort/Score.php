<?php

namespace Drupal\search\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Sort handler for sorting by search score.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("search_score")
 */
class Score extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Check to see if the search filter/argument added 'score' to the table.
    // Our filter stores it as $handler->search_score -- and we also
    // need to check its relationship to make sure that we're using the same
    // one or obviously this won't work.
    foreach (['filter', 'argument'] as $type) {
      foreach ($this->view->{$type} as $handler) {
        if (isset($handler->search_score) && $handler->relationship == $this->relationship) {
          $this->query->addOrderBy(NULL, NULL, $this->options['order'], $handler->search_score);
          $this->tableAlias = $handler->tableAlias;
          return;
        }
      }
    }

    // Do nothing if there is no filter/argument in place. There is no way
    // to sort on scores.
  }

}
