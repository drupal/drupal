<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\sort\Thread.
 */

namespace Drupal\comment\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Sort handler for ordering by thread.
 *
 * @ingroup views_sort_handlers
 *
 * @PluginID("comment_thread")
 */
class Thread extends SortPluginBase {

  public function query() {
    $this->ensureMyTable();

    //Read comment_render() in comment.module for an explanation of the
    //thinking behind this sort.
    if ($this->options['order'] == 'DESC') {
      $this->query->addOrderBy($this->tableAlias, $this->realField, $this->options['order']);
    }
    else {
      $alias = $this->tableAlias . '_' . $this->realField . 'asc';
      //@todo is this secure?
      $this->query->addOrderBy(NULL, "SUBSTRING({$this->tableAlias}.{$this->realField}, 1, (LENGTH({$this->tableAlias}.{$this->realField}) - 1))", $this->options['order'], $alias);
    }
  }

}
