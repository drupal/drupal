<?php

/**
 * @file
 * Definition of views_handler_sort_comment_thread.
 */

namespace Views\comment\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Sort handler for ordering by thread.
 *
 * @ingroup views_sort_handlers
 */

/**
 * @Plugin(
 *   id = "comment_thread"
 * )
 */
class Thread extends SortPluginBase {
  function query() {
    $this->ensure_my_table();

    //Read comment_render() in comment.module for an explanation of the
    //thinking behind this sort.
    if ($this->options['order'] == 'DESC') {
      $this->query->add_orderby($this->table_alias, $this->real_field, $this->options['order']);
    }
    else {
      $alias = $this->table_alias . '_' . $this->real_field . 'asc';
      //@todo is this secure?
      $this->query->add_orderby(NULL, "SUBSTRING({$this->table_alias}.{$this->real_field}, 1, (LENGTH({$this->table_alias}.{$this->real_field}) - 1))", $this->options['order'], $alias);
    }
  }
}
