<?php

/**
 * @file
 * Definition of views_handler_filter_comment_user_uid.
 */

namespace Drupal\comment\Plugin\views\filter;

use Drupal\views\Plugins\views\filter\FilterPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Filter handler to accept a user id to check for nodes that user posted or
 * commented on.
 *
 * @ingroup views_filter_handlers
 */

/**
 * @Plugin(
 *   plugin_id = "comment_user_uid"
 * )
 */
class UserUid extends FilterPluginBase {
  function query() {
    $this->ensure_my_table();

    $subselect = db_select('comment', 'c');
    $subselect->addField('c', 'cid');
    $subselect->condition('c.uid', $this->value, $this->operator);
    $subselect->where("c.nid = $this->table_alias.nid");

    $condition = db_or()
      ->condition("$this->table_alias.uid", $this->value, $this->operator)
      ->exists($subselect);

    $this->query->add_where($this->options['group'], $condition);
  }
}
