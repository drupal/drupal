<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\filter\UserUid.
 */

namespace Drupal\comment\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Filter handler to accept a user id to check for nodes that user posted or
 * commented on.
 *
 * @ingroup views_filter_handlers
 *
 * @Plugin(
 *   id = "comment_user_uid",
 *   module = "comment"
 * )
 */
class UserUid extends FilterPluginBase {

  public function query() {
    $this->ensureMyTable();

    $subselect = db_select('comment', 'c');
    $subselect->addField('c', 'cid');
    $subselect->condition('c.uid', $this->value, $this->operator);
    $subselect->where("c.nid = $this->tableAlias.nid");

    $condition = db_or()
      ->condition("$this->tableAlias.uid", $this->value, $this->operator)
      ->exists($subselect);

    $this->query->add_where($this->options['group'], $condition);
  }

}
