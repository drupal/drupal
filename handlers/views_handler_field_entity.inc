<?php

/**
 * @file
 * Definition of views_handler_field_entity.
 */

/**
 * A handler to display data from entity objects.
 *
 * Fields based upon this handler work with all query-backends if the tables
 * used by the query backend have an 'entity type' specified. In order to
 * make fields based upon this handler automatically available to all compatible
 * query backends, the views field can be defined in the table
 * @code views_entity_{ENTITY_TYPE} @endcode.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_entity extends views_handler_field {

  /**
   * Stores the entity type which is loaded by this field.
   */
  public $entity_type;

  /**
   * Stores all entites which are in the result.
   */
  public $entities;

  /**
   * The base field of the entity type assosiated with this field.
   */
  public $base_field;

  /**
   * Initialize the entity type.
   */
  public function init(&$view, &$options) {
    parent::init($view, $options);

    // Initialize the entity-type used.
    $table_data = views_fetch_data($this->table);
    $this->entity_type = $table_data['table']['entity type'];
  }

  /**
   * Overriden to add the field for the entity id.
   */
  function query() {
    $this->table_alias = $base_table = $this->view->base_table;
    $this->base_field = $this->view->base_field;

    if (!empty($this->relationship)) {
      foreach ($this->view->relationship as $relationship) {
        if ($relationship->alias == $this->relationship) {
          $base_table = $relationship->definition['base'];
          $this->table_alias = $relationship->alias;

          $table_data = views_fetch_data($base_table);
          $this->base_field = empty($relationship->definition['base field']) ? $table_data['table']['base']['field'] : $relationship->definition['base field'];
        }
      }
    }

    // Add the field if the query back-end implements an add_field() method,
    // just like the default back-end.
    if (method_exists($this->query, 'add_field')) {
      $this->field_alias = $this->query->add_field($this->table_alias, $this->base_field, '');
    }

    $this->add_additional_fields();
  }

  /**
   * Load the entities for all rows that are about to be displayed.
   */
  function pre_render(&$values) {
    if (!empty($values)) {
      list($this->entity_type, $this->entities) = $this->query->get_result_entities($values, !empty($this->relationship) ? $this->relationship : NULL, $this->field_alias);
    }
  }

  /**
   * Overridden to return the entity object, or a certain property of the entity.
   */
  function get_value($values, $field = NULL) {
    if (isset($this->entities[$this->view->row_index])) {
      $entity = $this->entities[$this->view->row_index];
      // Support to get a certain part of the entity.
      if (isset($field) && isset($entity->{$field})) {
        return $entity->{$field};
      }
      // Support to get a part of the values as the normal get_value.
      elseif (isset($field) && isset($values->{$this->aliases[$field]})) {
        return $values->{$this->aliases[$field]};
      }
      else {
        return $entity;
      }
    }
    return FALSE;
  }
}
