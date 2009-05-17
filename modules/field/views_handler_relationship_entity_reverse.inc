<?php

/**
 * @file
 * Definition of views_handler_relationship_entity_reverse.
 */

/**
 * A relationship handlers which reverse entity references.
 *
 * @ingroup views_relationship_handlers
 */
class views_handler_relationship_entity_reverse extends views_handler_relationship  {
  function init(&$view, &$options) {
    parent::init($view, $options);

    $this->field_info = field_info_field($this->definition['field_name']);
  }

  /**
   * Called to implement a relationship in a query.
   */
  function query() {
    $this->ensure_my_table();
    // First, relate our base table to the current base table to the
    // field, using the base table's id field to the field's column.
    $views_data = views_fetch_data($this->table);
    $left_field = $views_data['table']['base']['field'];

    $first = array(
      'left_table' => $this->table_alias,
      'left_field' => $left_field,
      'table' => $this->definition['field table'],
      'field' => $this->definition['field field'],
    );
    if (!empty($this->options['required'])) {
      $first['type'] = 'INNER';
    }

    if (!empty($this->definition['join_extra'])) {
      $first['extra'] = $this->definition['join_extra'];
    }

    if (!empty($this->definition['join_handler']) && class_exists($this->definition['join_handler'])) {
      $first_join = new $this->definition['join_handler'];
    }
    else {
      $first_join = new views_join();
    }
    $first_join->definition = $first;
    $first_join->construct();
    $first_join->adjusted = TRUE;

    $this->first_alias = $this->query->add_table($this->definition['field table'], $this->relationship, $first_join);

    // Second, relate the field table to the entity specified using
    // the entity id on the field table and the entity's id field.
    $second = array(
      'left_table' => $this->first_alias,
      'left_field' => 'entity_id',
      'table' => $this->definition['base'],
      'field' => $this->definition['base field'],
    );

    if (!empty($this->options['required'])) {
      $second['type'] = 'INNER';
    }

    if (!empty($this->definition['join_handler']) && class_exists($this->definition['join_handler'])) {
      $second_join = new $this->definition['join_handler'];
    }
    else {
      $second_join = new views_join();
    }
    $second_join->definition = $second;
    $second_join->construct();
    $second_join->adjusted = TRUE;

    // use a short alias for this:
    $alias = $this->definition['field_name'] . '_' . $this->table;

    $this->alias = $this->query->add_relationship($alias, $second_join, $this->definition['base'], $this->relationship);
  }
}
