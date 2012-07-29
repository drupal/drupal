<?php

/**
 * @file
 * Definition of views_handler_sort_ncs_last_comment_name.
 */

namespace Drupal\comment\Plugin\views\sort;

use Drupal\views\Join;
use Drupal\views\Plugins\views\sort\SortPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Sort handler to sort by last comment name which might be in 2 different
 * fields.
 *
 * @ingroup views_sort_handlers
 */

/**
 * @Plugin(
 *   plugin_id = "ncs_last_comment_name"
 * )
 */
class NcsLastCommentName extends SortPluginBase {
  function query() {
    $this->ensure_my_table();
    $join = new Join();
    $join->construct('users', $this->table_alias, 'last_comment_uid', 'uid');

    // @todo this might be safer if we had an ensure_relationship rather than guessing
    // the table alias. Though if we did that we'd be guessing the relationship name
    // so that doesn't matter that much.
//    $this->user_table = $this->query->add_relationship(NULL, $join, 'users', $this->relationship);
    $this->user_table = $this->query->ensure_table('ncs_users', $this->relationship, $join);
    $this->user_field = $this->query->add_field($this->user_table, 'name');

    // Add the field.
    $this->query->add_orderby(NULL, "LOWER(COALESCE($this->user_table.name, $this->table_alias.$this->field))", $this->options['order'], $this->table_alias . '_' . $this->field);
  }
}
