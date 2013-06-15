<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\sort\NcsLastCommentName.
 */

namespace Drupal\comment\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * Sort handler to sort by last comment name which might be in 2 different
 * fields.
 *
 * @ingroup views_sort_handlers
 *
 * @PluginID("comment_ncs_last_comment_name")
 */
class NcsLastCommentName extends SortPluginBase {

  public function query() {
    $this->ensureMyTable();
    $definition = array(
      'table' => 'users',
      'field' => 'uid',
      'left_table' => $this->tableAlias,
      'left_field' => 'last_comment_uid',
    );
    $join = drupal_container()->get('plugin.manager.views.join')->createInstance('standard', $definition);

    // @todo this might be safer if we had an ensure_relationship rather than guessing
    // the table alias. Though if we did that we'd be guessing the relationship name
    // so that doesn't matter that much.
    $this->user_table = $this->query->ensureTable('ncs_users', $this->relationship, $join);
    $this->user_field = $this->query->addField($this->user_table, 'name');

    // Add the field.
    $this->query->addOrderBy(NULL, "LOWER(COALESCE($this->user_table.name, $this->tableAlias.$this->field))", $this->options['order'], $this->tableAlias . '_' . $this->field);
  }

}
