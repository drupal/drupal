<?php

/**
 * @file
 * Views' relationship handlers.
 */

/**
 * @defgroup views_relationship_handlers Views relationship handlers
 * @{
 * Handlers to tell Views how to create alternate relationships.
 */

/**
 * Simple relationship handler that allows a new version of the primary table
 * to be linked in.
 *
 * The base relationship handler can only handle a single join. Some relationships
 * are more complex and might require chains of joins; for those, you must
 * utilize a custom relationship handler.
 *
 * Definition items:
 * - base: The new base table this relationship will be adding. This does not
 *   have to be a declared base table, but if there are no tables that
 *   utilize this base table, it won't be very effective.
 * - base field: The field to use in the relationship; if left out this will be
 *   assumed to be the primary field.
 * - relationship table: The actual table this relationship operates against.
 *   This is analogous to using a 'table' override.
 * - relationship field: The actual field this relationship operates against.
 *   This is analogous to using a 'real field' override.
 * - label: The default label to provide for this relationship, which is
 *   shown in parentheses next to any field/sort/filter/argument that uses
 *   the relationship.
 *
 * @ingroup views_relationship_handlers
 */
class views_handler_relationship extends views_handler {
  /**
   * Init handler to let relationships live on tables other than
   * the table they operate on.
   */
  function init(&$view, &$options) {
    parent::init($view, $options);
    if (isset($this->definition['relationship table'])) {
      $this->table = $this->definition['relationship table'];
    }
    if (isset($this->definition['relationship field'])) {
      // Set both real_field and field so custom handler
      // can rely on the old field value.
      $this->real_field = $this->field = $this->definition['relationship field'];
    }
  }

  /**
   * Get this field's label.
   */
  function label() {
    if (!isset($this->options['label'])) {
      return $this->ui_name();
    }
    return $this->options['label'];
  }

  function option_definition() {
    $options = parent::option_definition();


    // Relationships definitions should define a default label, but if they aren't get another default value.
    if (!empty($this->definition['label'])) {
      $label = $this->definition['label'];
    }
    else {
      $label = !empty($this->definition['field']) ? $this->definition['field'] : $this->definition['base field'];
    }

    $options['label'] = array('default' => $label, 'translatable' => TRUE);
    $options['required'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Default options form that provides the label widget that all fields
   * should have.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Identifier'),
      '#default_value' => isset($this->options['label']) ? $this->options['label'] : '',
      '#description' => t('Edit the administrative label displayed when referencing this relationship from filters, etc.'),
      '#required' => TRUE,
    );

    $form['required'] = array(
      '#type' => 'checkbox',
      '#title' => t('Require this relationship'),
      '#description' => t('Enable to hide items that do not contain this relationship'),
      '#default_value' => !empty($this->options['required']),
    );
  }

  /**
   * Called to implement a relationship in a query.
   */
  function query() {
    // Figure out what base table this relationship brings to the party.
    $table_data = views_fetch_data($this->definition['base']);
    $base_field = empty($this->definition['base field']) ? $table_data['table']['base']['field'] : $this->definition['base field'];

    $this->ensure_my_table();

    $def = $this->definition;
    $def['table'] = $this->definition['base'];
    $def['field'] = $base_field;
    $def['left_table'] = $this->table_alias;
    $def['left_field'] = $this->real_field;
    if (!empty($this->options['required'])) {
      $def['type'] = 'INNER';
    }

    if (!empty($this->definition['extra'])) {
      $def['extra'] = $this->definition['extra'];
    }

    if (!empty($def['join_handler']) && class_exists($def['join_handler'])) {
      $join = new $def['join_handler'];
    }
    else {
      $join = new views_join();
    }

    $join->definition = $def;
    $join->options = $this->options;
    $join->construct();
    $join->adjusted = TRUE;

    // use a short alias for this:
    $alias = $def['table'] . '_' . $this->table;

    $this->alias = $this->query->add_relationship($alias, $join, $this->definition['base'], $this->relationship);

    // Add access tags if the base table provide it.
    if (empty($this->query->options['disable_sql_rewrite']) && isset($table_data['table']['base']['access query tag'])) {
      $access_tag = $table_data['table']['base']['access query tag'];
      $this->query->add_tag($access_tag);
    }
  }

  /**
   * You can't groupby a relationship.
   */
  function use_group_by() {
    return FALSE;
  }
}

/**
 * A special handler to take the place of missing or broken handlers.
 *
 * @ingroup views_relationship_handlers
 */
class views_handler_relationship_broken extends views_handler_relationship {
  function ui_name($short = FALSE) {
    return t('Broken/missing handler');
  }

  function ensure_my_table() { /* No table to ensure! */ }
  function query() { /* No query to run */ }
  function options_form(&$form, &$form_state) {
    $form['markup'] = array(
      '#markup' => '<div class="form-item description">' . t('The handler for this item is broken or missing and cannot be used. If a module provided the handler and was disabled, re-enabling the module may restore it. Otherwise, you should probably delete this item.') . '</div>',
    );
  }

  /**
   * Determine if the handler is considered 'broken'
   */
  function broken() { return TRUE; }
}

/**
 * @}
 */
