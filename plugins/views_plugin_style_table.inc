<?php

/**
 * @file
 * Contains the table style plugin.
 */

/**
 * Style plugin to render each item as a row in a table.
 *
 * @ingroup views_style_plugins
 */
class views_plugin_style_table extends views_plugin_style {

  /**
   * Contains the current active sort column.
   * @var string
   */
  public $active;

  /**
   * Contains the current active sort order, either desc or asc.
   * @var string
   */
  public $order;

  function option_definition() {
    $options = parent::option_definition();

    $options['columns'] = array('default' => array());
    $options['default'] = array('default' => '');
    $options['info'] = array('default' => array());
    $options['override'] = array('default' => TRUE, 'bool' => TRUE);
    $options['sticky'] = array('default' => FALSE, 'bool' => TRUE);
    $options['order'] = array('default' => 'asc');
    $options['summary'] = array('default' => '', 'translatable' => TRUE);
    $options['empty_table'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Determine if we should provide sorting based upon $_GET inputs
   *
   * @return bool
   */
  function build_sort() {
    if (!isset($_GET['order']) && ($this->options['default'] == -1 || empty($this->view->field[$this->options['default']]))) {
      return TRUE;
    }

    // If a sort we don't know anything about gets through, exit gracefully.
    if (isset($_GET['order']) && empty($this->view->field[$_GET['order']])) {
      return TRUE;
    }

    // Let the builder know whether or not we're overriding the default sorts.
    return empty($this->options['override']);
  }

  /**
   * Add our actual sort criteria
   */
  function build_sort_post() {
    if (!isset($_GET['order'])) {
      // check for a 'default' clicksort. If there isn't one, exit gracefully.
      if (empty($this->options['default'])) {
        return;
      }
      $sort = $this->options['default'];
      if (!empty($this->options['info'][$sort]['default_sort_order'])) {
        $this->order = $this->options['info'][$sort]['default_sort_order'];
      }
      else {
        $this->order = !empty($this->options['order']) ? $this->options['order'] : 'asc';
      }
    }
    else {
      $sort = $_GET['order'];
      // Store the $order for later use.
      $this->order = !empty($_GET['sort']) ? strtolower($_GET['sort']) : 'asc';
    }

    // If a sort we don't know anything about gets through, exit gracefully.
    if (empty($this->view->field[$sort])) {
      return;
    }

    // Ensure $this->order is valid.
    if ($this->order != 'asc' && $this->order != 'desc') {
      $this->order = 'asc';
    }

    // Store the $sort for later use.
    $this->active = $sort;

    // Tell the field to click sort.
    $this->view->field[$sort]->click_sort($this->order);
  }

  /**
   * Normalize a list of columns based upon the fields that are
   * available. This compares the fields stored in the style handler
   * to the list of fields actually in the view, removing fields that
   * have been removed and adding new fields in their own column.
   *
   * - Each field must be in a column.
   * - Each column must be based upon a field, and that field
   *   is somewhere in the column.
   * - Any fields not currently represented must be added.
   * - Columns must be re-ordered to match the fields.
   *
   * @param $columns
   *   An array of all fields; the key is the id of the field and the
   *   value is the id of the column the field should be in.
   * @param $fields
   *   The fields to use for the columns. If not provided, they will
   *   be requested from the current display. The running render should
   *   send the fields through, as they may be different than what the
   *   display has listed due to access control or other changes.
   *
   * @return array
   *    An array of all the sanitized columns.
   */
  function sanitize_columns($columns, $fields = NULL) {
    $sanitized = array();
    if ($fields === NULL) {
      $fields = $this->display->handler->get_option('fields');
    }
    // Preconfigure the sanitized array so that the order is retained.
    foreach ($fields as $field => $info) {
      // Set to itself so that if it isn't touched, it gets column
      // status automatically.
      $sanitized[$field] = $field;
    }

    foreach ($columns as $field => $column) {
      // first, make sure the field still exists.
      if (!isset($sanitized[$field])) {
        continue;
      }

      // If the field is the column, mark it so, or the column
      // it's set to is a column, that's ok
      if ($field == $column || $columns[$column] == $column && !empty($sanitized[$column])) {
        $sanitized[$field] = $column;
      }
      // Since we set the field to itself initially, ignoring
      // the condition is ok; the field will get its column
      // status back.
    }

    return $sanitized;
  }

  /**
   * Render the given style.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $handlers = $this->display->handler->get_handlers('field');
    if (empty($handlers)) {
      $form['error_markup'] = array(
        '#markup' => '<div class="error messages">' . t('You need at least one field before you can configure your table settings') . '</div>',
      );
      return;
    }

    $form['override'] = array(
      '#type' => 'checkbox',
      '#title' => t('Override normal sorting if click sorting is used'),
      '#default_value' => !empty($this->options['override']),
    );

    $form['sticky'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable Drupal style "sticky" table headers (Javascript)'),
      '#default_value' => !empty($this->options['sticky']),
      '#description' => t('(Sticky header effects will not be active for preview below, only on live output.)'),
    );

    $form['summary'] = array(
      '#type' => 'textfield',
      '#title' => t('Table summary'),
      '#description' => t('This value will be displayed as table-summary attribute in the html. Set this for better accessiblity of your site.'),
      '#default_value' => $this->options['summary'],
      '#maxlength' => 255,
    );

    // Note: views UI registers this theme handler on our behalf. Your module
    // will have to register your theme handlers if you do stuff like this.
    $form['#theme'] = 'views_ui_style_plugin_table';

    $columns = $this->sanitize_columns($this->options['columns']);

    // Create an array of allowed columns from the data we know:
    $field_names = $this->display->handler->get_field_labels();

    if (isset($this->options['default'])) {
      $default = $this->options['default'];
      if (!isset($columns[$default])) {
        $default = -1;
      }
    }
    else {
      $default = -1;
    }

    foreach ($columns as $field => $column) {
      $safe = str_replace(array('][', '_', ' '), '-', $field);
      // the $id of the column for dependency checking.
      $id = 'edit-style-options-columns-' . $safe;

      $form['columns'][$field] = array(
        '#type' => 'select',
        '#options' => $field_names,
        '#default_value' => $column,
      );
      if ($handlers[$field]->click_sortable()) {
        $form['info'][$field]['sortable'] = array(
          '#type' => 'checkbox',
          '#default_value' => !empty($this->options['info'][$field]['sortable']),
          '#dependency' => array($id => array($field)),
        );
        $form['info'][$field]['default_sort_order'] = array(
          '#type' => 'select',
          '#options' => array('asc' => t('Ascending'), 'desc' => t('Descending')),
          '#default_value' => !empty($this->options['info'][$field]['default_sort_order']) ? $this->options['info'][$field]['default_sort_order'] : 'asc',
          '#dependency_count' => 2,
          '#dependency' => array($id => array($field), 'edit-style-options-info-' . $safe . '-sortable' => array(1)),
        );
        // Provide an ID so we can have such things.
        $radio_id = drupal_html_id('edit-default-' . $field);
        $form['default'][$field] = array(
          '#type' => 'radio',
          '#return_value' => $field,
          '#parents' => array('style_options', 'default'),
          '#id' => $radio_id,
          // because 'radio' doesn't fully support '#id' =(
          '#attributes' => array('id' => $radio_id),
          '#default_value' => $default,
          '#dependency' => array($id => array($field)),
        );
      }
      $form['info'][$field]['align'] = array(
        '#type' => 'select',
        '#default_value' => !empty($this->options['info'][$field]['align']) ? $this->options['info'][$field]['align'] : '',
        '#options' => array(
          '' => t('None'),
          'views-align-left' => t('Left'),
          'views-align-center' => t('Center'),
          'views-align-right' => t('Right'),
          ),
        '#dependency' => array($id => array($field)),
      );
      $form['info'][$field]['separator'] = array(
        '#type' => 'textfield',
        '#size' => 10,
        '#default_value' => isset($this->options['info'][$field]['separator']) ? $this->options['info'][$field]['separator'] : '',
        '#dependency' => array($id => array($field)),
      );
      $form['info'][$field]['empty_column'] = array(
        '#type' => 'checkbox',
        '#default_value' => isset($this->options['info'][$field]['empty_column']) ? $this->options['info'][$field]['empty_column'] : FALSE,
        '#dependency' => array($id => array($field)),
      );

      // markup for the field name
      $form['info'][$field]['name'] = array(
        '#markup' => $field_names[$field],
      );
    }

    // Provide a radio for no default sort
    $form['default'][-1] = array(
      '#type' => 'radio',
      '#return_value' => -1,
      '#parents' => array('style_options', 'default'),
      '#id' => 'edit-default-0',
      '#default_value' => $default,
    );

    $form['empty_table'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the empty text in the table'),
      '#default_value' => $this->options['empty_table'],
      '#description' => t('Per default the table is hidden for an empty view. With this option it is posible to show an empty table with the text in it.'),
    );

    $form['description_markup'] = array(
      '#markup' => '<div class="description form-item">' . t('Place fields into columns; you may combine multiple fields into the same column. If you do, the separator in the column specified will be used to separate the fields. Check the sortable box to make that column click sortable, and check the default sort radio to determine which column will be sorted by default, if any. You may control column order and field labels in the fields section.') . '</div>',
    );
  }

  function even_empty() {
    return parent::even_empty() || !empty($this->options['empty_table']);
  }
}
