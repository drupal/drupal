<?php

/**
 * @file
 * Contains the base row style plugin.
 */

/**
 * @defgroup views_row_plugins Views row plugins
 * @{
 * Row plugins control how Views outputs an individual record.
 *
 * They are tightly coupled to style plugins, in that a style plugin is what
 * calls the row plugin.
 *
 * @see hook_views_plugins()
 */

/**
 * Default plugin to view a single row of a table. This is really just a wrapper around
 * a theme function.
 */
class views_plugin_row extends views_plugin {
  /**
   * Initialize the row plugin.
   */
  function init(&$view, &$display, $options = NULL) {
    $this->view = &$view;
    $this->display = &$display;

    // Overlay incoming options on top of defaults
    $this->unpack_options($this->options, isset($options) ? $options : $display->handler->get_option('row_options'));
  }

  function uses_fields() {
    return !empty($this->definition['uses fields']);
  }


  function option_definition() {
    $options = parent::option_definition();
    if (isset($this->base_table)) {
      $options['relationship'] = array('default' => 'none');
    }

    return $options;
  }

  /**
   * Provide a form for setting options.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    if (isset($this->base_table)) {
      $view = &$form_state['view'];

      // A whole bunch of code to figure out what relationships are valid for
      // this item.
      $relationships = $view->display_handler->get_option('relationships');
      $relationship_options = array();

      foreach ($relationships as $relationship) {
        $relationship_handler = views_get_handler($relationship['table'], $relationship['field'], 'relationship');

        // If this relationship is valid for this type, add it to the list.
        $data = views_fetch_data($relationship['table']);
        $base = $data[$relationship['field']]['relationship']['base'];
        if ($base == $this->base_table) {
          $relationship_handler->init($view, $relationship);
          $relationship_options[$relationship['id']] = $relationship_handler->label();
        }
      }

      if (!empty($relationship_options)) {
        $relationship_options = array_merge(array('none' => t('Do not use a relationship')), $relationship_options);
        $rel = empty($this->options['relationship']) ? 'none' : $this->options['relationship'];
        if (empty($relationship_options[$rel])) {
          // Pick the first relationship.
          $rel = key($relationship_options);
        }

        $form['relationship'] = array(
          '#type' => 'select',
          '#title' => t('Relationship'),
          '#options' => $relationship_options,
          '#default_value' => $rel,
        );
      }
      else {
        $form['relationship'] = array(
          '#type' => 'value',
          '#value' => 'none',
        );
      }
    }
  }

  /**
   * Validate the options form.
   */
  function options_validate(&$form, &$form_state) { }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  function options_submit(&$form, &$form_state) { }

  function query() {
    if (isset($this->base_table)) {
      if (isset($this->options['relationship']) && isset($this->view->relationship[$this->options['relationship']])) {
        $relationship = $this->view->relationship[$this->options['relationship']];
        $this->field_alias = $this->view->query->add_field($relationship->alias, $this->base_field);
      }
      else {
        $this->field_alias = $this->view->query->add_field($this->base_table, $this->base_field);
      }
    }
  }

  /**
   * Allow the style to do stuff before each row is rendered.
   *
   * @param $result
   *   The full array of results from the query.
   */
  function pre_render($result) { }

  /**
   * Render a row object. This usually passes through to a theme template
   * of some form, but not always.
   *
   * @param stdClass $row
   *   A single row of the query result, so an element of $view->result.
   *
   * @return string
   *   The rendered output of a single row, used by the style plugin.
   */
  function render($row) {
    return theme($this->theme_functions(),
      array(
        'view' => $this->view,
        'options' => $this->options,
        'row' => $row,
        'field_alias' => isset($this->field_alias) ? $this->field_alias : '',
      ));
  }
}

/**
 * @}
 */
