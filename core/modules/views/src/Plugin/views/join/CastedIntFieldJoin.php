<?php

namespace Drupal\views\Plugin\views\join;

use Drupal\views\Attribute\ViewsJoin;

/**
 * Implementation for the "casted_int_field_join" join.
 *
 * This is needed for columns that are using a single table for tracking the
 * relationship to their items, but the referenced item IDs can be either
 * integers or strings and most DB engines (with MySQL as a notable exception)
 * are strict when comparing numbers and strings.
 *
 * @ingroup views_join_handlers
 */
#[ViewsJoin("casted_int_field_join")]
class CastedIntFieldJoin extends JoinPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildJoin($select_query, $table, $view_query) {
    if (empty($this->configuration['table formula'])) {
      $right_table = $this->table;
    }
    else {
      $right_table = $this->configuration['table formula'];
    }

    if ($this->leftTable) {
      $left_table = $view_query->getTableInfo($this->leftTable);
      $left_field = $this->leftFormula ?: "$left_table[alias].$this->leftField";
    }
    else {
      // This can be used if left_field is a formula or something. It should be
      // used only *very* rarely.
      $left_field = $this->leftField;
      $left_table = NULL;
    }

    $right_field = "{$table['alias']}.$this->field";

    assert(!isset($this->configuration['cast']) || in_array($this->configuration['cast'], ['right', 'left']));
    if (isset($this->configuration['cast']) && $this->configuration['cast'] === 'left') {
      $left_field = \Drupal::service('views.cast_sql')->getFieldAsInt($left_field);
    }
    else {
      $right_field = \Drupal::service('views.cast_sql')->getFieldAsInt($right_field);
    }

    $condition = "$left_field {$this->configuration['operator']} $right_field";
    $arguments = [];

    // Tack on the extra.
    if (isset($this->extra)) {
      $this->joinAddExtra($arguments, $condition, $table, $select_query, $left_table);
    }

    $select_query->addJoin($this->type, $right_table, $table['alias'], $condition, $arguments);
  }

}
