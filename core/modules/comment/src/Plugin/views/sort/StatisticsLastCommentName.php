<?php

namespace Drupal\comment\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Sort handler to sort by last comment name which might be in 2 different
 * fields.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("comment_ces_last_comment_name")
 */
class StatisticsLastCommentName extends SortPluginBase {

  public function query() {
    $this->ensureMyTable();
    $definition = [
      'table' => 'users_field_data',
      'field' => 'uid',
      'left_table' => 'comment_entity_statistics',
      'left_field' => 'last_comment_uid',
    ];
    $join = \Drupal::service('plugin.manager.views.join')->createInstance('standard', $definition);

    // @todo this might be safer if we had an ensure_relationship rather than guessing
    // the table alias. Though if we did that we'd be guessing the relationship name
    // so that doesn't matter that much.
    $this->user_table = $this->query->ensureTable('ces_users', $this->relationship, $join);
    $this->user_field = $this->query->addField($this->user_table, 'name');

    // Add the field.
    $this->query->addOrderBy(NULL, "LOWER(COALESCE($this->user_table.name, $this->tableAlias.$this->field))", $this->options['order'], $this->tableAlias . '_' . $this->field);
  }

}
