<?php
// $Id: views_plugin_style.inc,v 1.8.4.10 2010/12/10 08:12:31 dereine Exp $

/**
 * @defgroup views_style_plugins Views' style plugins
 * @{
 * Style plugins control how a view is rendered. For example, they
 * can choose to display a collection of fields, node_view() output,
 * table output, or any kind of crazy output they want.
 *
 * Many style plugins can have an optional 'row' plugin, that displays
 * a single record. Not all style plugins can utilize this, so it is
 * up to the plugin to set this up and call through to the row plugin.
 *
 * @see hook_views_plugins
 */

/**
 * Base class to define a style plugin handler.
 */
class views_plugin_style extends views_plugin {
  /**
   * Initialize a style plugin.
   *
   * @param $view
   * @param $display
   * @param $options
   *   The style options might come externally as the style can be sourced
   *   from at least two locations. If it's not included, look on the display.
   */
  function init(&$view, &$display, $options = NULL) {
    $this->view = &$view;
    $this->display = &$display;

    // Overlay incoming options on top of defaults
    $this->unpack_options($this->options, isset($options) ? $options : $display->handler->get_option('style_options'));

    if ($this->uses_row_plugin() && $display->handler->get_option('row_plugin')) {
      $this->row_plugin = $display->handler->get_plugin('row');
    }

    $this->options += array(
      'grouping' => '',
    );

    $this->definition += array(
      'uses grouping' => TRUE,
    );
  }

  function destroy() {
    parent::destroy();

    if (isset($this->row_plugin)) {
      $this->row_plugin->destroy();
    }
  }

  /**
   * Return TRUE if this style also uses a row plugin.
   */
  function uses_row_plugin() {
    return !empty($this->definition['uses row plugin']);
  }

  /**
   * Return TRUE if this style also uses a row plugin.
   */
  function uses_row_class() {
    return !empty($this->definition['uses row class']);
  }

  /**
   * Return TRUE if this style also uses fields.
   */
  function uses_fields() {
    // If we use a row plugin, ask the row plugin. Chances are, we don't
    // care, it does.
    if ($this->uses_row_plugin() && !empty($this->row_plugin)) {
      return $this->row_plugin->uses_fields();
    }
    // Otherwise, maybe we do.
    return !empty($this->definition['uses fields']);
  }

  /**
   * Return TRUE if this style uses tokens.
   *
   * Used to ensure we don't fetch tokens when not needed for performance.
   */
  function uses_tokens() {
    if ($this->uses_row_class()) {
      $class = $this->options['row_class'];
      if (strpos($class, '[') !== FALSE || strpos($class, '!') !== FALSE || strpos($class, '%') !== FALSE) {
        return TRUE;
      }
    }
  }

  /**
   * Return the token replaced row class for the specified row.
   */
  function get_row_class($row_index) {
    $class = $this->options['row_class'];
    if ($this->uses_fields() && $this->view->field) {
      $class = $this->tokenize_value($class, $row_index);
    }

    return drupal_clean_css_identifier($class);
  }

  /**
   * Take a value and apply token replacement logic to it.
   */
  function tokenize_value($value, $row_index) {
    if (strpos($value, '[') !== FALSE || strpos($value, '!') !== FALSE || strpos($value, '%') !== FALSE) {
      $fake_item = array(
        'alter_text' => TRUE,
        'text' => $value,
      );

      $tokens = $this->row_tokens[$row_index];
      // Grab a random field handler to perform the render.
      $field = end($this->view->field);
      $value = strip_tags($field->render_altered($fake_item, $tokens));
    }

    return $value;
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['grouping'] = array('default' => '');
    if ($this->uses_row_class()) {
      $options['row_class'] = array('default' => '');
    }

    return $options;
  }

  function options_form(&$form, &$form_state) {
    // Only fields-based views can handle grouping.  Style plugins can also exclude
    // themselves from being groupable by setting their "use grouping" definiton
    // key to FALSE.
    // @TODO: Document "uses grouping" in docs.php when docs.php is written.
    if ($this->uses_fields() && $this->definition['uses grouping']) {
      $options = array('' => t('- None -'));
      $options += $this->display->handler->get_field_labels();

      // If there are no fields, we can't group on them.
      if (count($options) > 1) {
        $form['grouping'] = array(
          '#type' => 'select',
          '#title' => t('Grouping field'),
          '#options' => $options,
          '#default_value' => $this->options['grouping'],
          '#description' => t('You may optionally specify a field by which to group the records. Leave blank to not group.'),
        );
      }
    }

    if ($this->uses_row_class()) {
      $form['row_class'] = array(
        '#title' => t('Row class'),
        '#description' => t('The class to provide on each row.'),
        '#type' => 'textfield',
        '#default_value' => $this->options['row_class'],
      );

      if ($this->uses_fields()) {
        $form['row_class']['#description'] .= ' ' . t('You may use field tokens from as per the "Replacement patterns" used in "Rewrite the output of this field" for all fields.');
      }
    }
  }

  /**
   * Called by the view builder to see if this style handler wants to
   * interfere with the sorts. If so it should build; if it returns
   * any non-TRUE value, normal sorting will NOT be added to the query.
   */
  function build_sort() { return TRUE; }

  /**
   * Called by the view builder to let the style build a second set of
   * sorts that will come after any other sorts in the view.
   */
  function build_sort_post() { }

  /**
   * Allow the style to do stuff before each row is rendered.
   *
   * @param $result
   *   The full array of results from the query.
   */
  function pre_render($result) {
    if (!empty($this->row_plugin)) {
      $this->row_plugin->pre_render($result);
    }
  }

  /**
   * Render the display in this style.
   */
  function render() {
    if ($this->uses_row_plugin() && empty($this->row_plugin)) {
      debug('views_plugin_style_default: Missing row plugin');
      return;
    }

    // Group the rows according to the grouping field, if specified.
    $sets = $this->render_grouping($this->view->result, $this->options['grouping']);

    // Render each group separately and concatenate.  Plugins may override this
    // method if they wish some other way of handling grouping.
    $output = '';
    foreach ($sets as $title => $records) {
      if ($this->uses_row_plugin()) {
        $rows = array();
        foreach ($records as $row_index => $row) {
          $this->view->row_index = $row_index;
          $rows[$row_index] = $this->row_plugin->render($row);
        }
      }
      else {
        $rows = $records;
      }

      $output .= theme($this->theme_functions(),
        array(
          'view' => $this->view,
          'options' => $this->options,
          'rows' => $rows,
          'title' => $title)
        );
    }
    unset($this->view->row_index);
    return $output;
  }

  /**
   * Group records as needed for rendering.
   *
   * @param $records
   *   An array of records from the view to group.
   * @param $grouping_field
   *   The field id on which to group.  If empty, the result set will be given
   *   a single group with an empty string as a label.
   * @return
   *   The grouped record set.
   */
  function render_grouping($records, $grouping_field = '') {
    // Make sure fields are rendered
    $this->render_fields($this->view->result);
    $sets = array();
    if ($grouping_field) {
      foreach ($records as $index => $row) {
        $grouping = '';
        // Group on the rendered version of the field, not the raw.  That way,
        // we can control any special formatting of the grouping field through
        // the admin or theme layer or anywhere else we'd like.
        if (isset($this->view->field[$grouping_field])) {
          $grouping = $this->get_field($index, $grouping_field);
          if ($this->view->field[$grouping_field]->options['label']) {
            $grouping = $this->view->field[$grouping_field]->options['label'] . ': ' . $grouping;
          }
        }
        $sets[$grouping][$index] = $row;
      }
    }
    else {
      // Create a single group with an empty grouping field.
      $sets[''] = $records;
    }
    return $sets;
  }

  /**
   * Render all of the fields for a given style and store them on the object.
   *
   * @param $result
   *   The result array from $view->result
   */
  function render_fields($result) {
    if (!$this->uses_fields()) {
      return;
    }

    if (isset($this->rendered_fields)) {
      return $this->rendered_fields;
    }

    $this->view->row_index = 0;
    $keys = array_keys($this->view->field);
    foreach ($result as $count => $row) {
      $this->view->row_index = $count;
      foreach ($keys as $id) {
        $this->rendered_fields[$count][$id] = $this->view->field[$id]->theme($row);
      }

      if ($this->uses_tokens()) {
        $this->row_tokens[$count] = $this->view->field[$id]->get_render_tokens(array());
      }
    }
    unset($this->view->row_index);
  }

  /**
   * Get a rendered field.
   *
   * @param $index
   *   The index count of the row.
   * @param $field
   *    The id of the field.
   */
  function get_field($index, $field) {
    if (!isset($this->rendered_fields)) {
      $this->render_fields($this->view->result);
    }

    if (isset($this->rendered_fields[$index][$field])) {
      return $this->rendered_fields[$index][$field];
    }
  }

  function validate() {
    $errors = parent::validate();

    if ($this->uses_row_plugin()) {
      $plugin = $this->display->handler->get_plugin('row');
      if (empty($plugin)) {
        $errors[] = t('Style @style requires a row style but the row plugin is invalid.', array('@style' => $this->definition['title']));
      }
      else {
        $result = $plugin->validate();
        if (!empty($result) && is_array($result)) {
          $errors = array_merge($errors, $result);
        }
      }
    }
    return $errors;
  }

  function query() {
    parent::query();
    if (isset($this->row_plugin)) {
      $this->row_plugin->query();
    }
  }
}

/**
 * @}
 */

