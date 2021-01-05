<?php

namespace Drupal\comment\Plugin\views\filter;

use Drupal\Core\Database\Database;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter handler to accept a user id to check for nodes that user posted or
 * commented on.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("comment_user_uid")
 */
class UserUid extends FilterPluginBase {

  public function query() {
    $this->ensureMyTable();

    $subselect = Database::getConnection()->select('comment_field_data', 'c');
    $subselect->addField('c', 'cid');
    $subselect->condition('c.uid', $this->value, $this->operator);

    $entity_id = $this->definition['entity_id'];
    $entity_type = $this->definition['entity_type'];
    $subselect->where("[c].[entity_id] = [$this->tableAlias].[$entity_id]");
    $subselect->condition('c.entity_type', $entity_type);

    $condition = ($this->view->query->getConnection()->condition('OR'))
      ->condition("$this->tableAlias.uid", $this->value, $this->operator)
      ->exists($subselect);

    $this->query->addWhere($this->options['group'], $condition);
  }

}
