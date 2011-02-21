<?php
// $Id: views_handler_field_field.inc,v 1.1.2.20 2011/01/06 00:37:05 dereine Exp $

/**
 * Helper function: Return an array of formatter options for a field type.
 *
 * Borrowed from field_ui.
 */
function _field_view_formatter_options($field_type = NULL) {
  $options = &drupal_static(__FUNCTION__);

  if (!isset($options)) {
    $field_types = field_info_field_types();
    $options = array();
    foreach (field_info_formatter_types() as $name => $formatter) {
      foreach ($formatter['field types'] as $formatter_field_type) {
        // Check that the field type exists.
        if (isset($field_types[$formatter_field_type])) {
          $options[$formatter_field_type][$name] = $formatter['label'];
        }
      }
    }
  }

  if ($field_type) {
    return !empty($options[$field_type]) ? $options[$field_type] : array();
  }
  return $options;
}

/**
 * A field that displays fields.
 */
class views_handler_field_field extends views_handler_field {
  /**
   * Called to add the field to a query.
   *
   * By default, the only columns added to the query are entity_id and
   * entity_type. This is because other needed data is fetched by entity_load().
   * Other columns are added only if they are used in groupings, or if
   * 'add fields to query' is specifically set to TRUE in the field definition.
   *
   * The 'add fields to query' switch is used by modules which need all data
   * present in the query itself (such as "sphinx").
   */
  function query($use_groupby = FALSE) {
    $base_table = $this->view->base_table;
    $base_field = $this->view->base_field;

    // If the current field is under a relationship you can't be sure that the
    // base table of the view is the base table of the current field.
    // For example a field from a node author on a node view does have users as base table.
    if (!empty($this->relationship)) {
      foreach ($this->view->relationship as $relationship) {
        if ($relationship->alias == $this->relationship) {
          $base_table = $relationship->definition['base'];
          $base_field = $relationship->definition['base field'];
        }
      }
    }

    $params = array();
    if ($use_groupby) {
      // When grouping on a "field API" field (whose "real_field" is set to
      // entity_id), retrieve the minimum entity_id to have a valid entity_id to
      // pass to field_view_field().
      $params = array(
        'function' => 'min',
      );
    }

    // We always need the base field (entity_id / revision_id).
    $this->field_alias = $this->query->add_field($base_table, $base_field, '', $params);

    // Get the entity type according to the base table of the field.
    // Then add it to the query as a formula. That way we can avoid joining
    // the field table if all we need is entity_id and entity_type.
    $this->entity_type = $entity_type = $this->definition['entity_tables'][$base_table];
    // The alias needs to be unique, so we use both the field table and the entity type.
    $entity_type_alias = $this->definition['table'] . '_' . $entity_type . '_entity_type';
    $this->aliases['entity_type'] = $this->query->add_field(NULL, "'$entity_type'", $entity_type_alias);

    $fields = $this->additional_fields;

    // We've already added entity_type, so we can remove it from the list.
    $entity_type_key = array_search('entity_type', $fields);
    if ($entity_type_key !== FALSE) {
      unset($fields[$entity_type_key]);
    }

    if ($use_groupby) {
      // Remove additional fields that are not the group_column or are not in
      // the additional group_columns as their presence in the query inhibits
      // grouping.
      $group_field_name = $this->definition['field_name'] . '_' . $this->options['group_column'];
      if (in_array($group_field_name, $fields)) {
        $fields = array($group_field_name => $group_field_name) + $this->options['group_columns'];
      }
    }

    // Add additional fields (and the table join itself) if needed.
    if ($use_groupby || !empty($this->definition['add fields to query'])) {
      $this->ensure_my_table();
      $this->add_additional_fields($fields);

      // Filter by language, if field translation is enabled.
      $field = $this->definition['field_info'];
      if (field_is_translatable($entity_type, $field)) {
        $column = $this->table_alias . ".language";
        $this->query->add_where(0, $column, $this->query->options['field_language']);
      }
    }

    // The revision id inhibits grouping.
    // So, stop here if we're using grouping, or if aren't adding all columns to
    // the query.
    if ($use_groupby || empty($this->definition['add fields to query'])) {
      return;
    }

    $this->add_additional_fields(array('revision_id'));
  }

  /**
   * Called to determine what to tell the clicksorter.
   */
  function click_sort($order) {
    $column = _field_sql_storage_columnname($this->definition['field_name'], $this->options['click_sort_column']);
    // The field has already been added to the query, just add a sort on it.
    $this->query->add_orderby(NULL, NULL, $order, $this->aliases[$column]);
  }

  function option_definition() {
    $options = parent::option_definition();

    $field = $this->definition['field_info'];
    $field_type = field_info_field_types($field['type']);
    $column_names = array_keys($field['columns']);

    // If the field has a "value" column, we probably need that one.
    $options['click_sort_column'] = array(
      'default' => in_array('value', $column_names) ? 'value' : '',
    );
    $options['type'] = array(
      'default' => $field_type['default_formatter'],
    );
    $options['settings'] = array(
      'default' => array(),
    );
    $options['group_column'] = array(
      'default' => in_array('value', $column_names) ? 'value' : $column_names[0],
    );
    $options['group_columns'] = array(
      'default' => array(),
    );

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $field = $this->definition['field_info'];
    $formatters = _field_view_formatter_options($field['type']);
    $column_names = array_keys($field['columns']);

    // No need to ask the user anything if the field has only one column.
    if (count($field['columns']) == 1) {
      $form['click_sort_column'] = array(
        '#type' => 'value',
        '#value' => $column_names[0],
      );
    }
    else {
      $form['click_sort_column'] = array(
        '#type' => 'select',
        '#title' => t('Click sort column'),
        '#options' => drupal_map_assoc($column_names),
        '#default_value' => $this->options['click_sort_column'],
        '#description' => t('Used by Style: Table to determine the actual column to click sort the field on. The default is usually fine.'),
      );
    }

    $form['type'] = array(
      '#type' => 'select',
      '#title' => t('Formatter'),
      '#options' => $formatters,
      '#default_value' => $this->options['type'],
      '#ajax' => array(
        'path' => views_ui_build_form_url($form_state),
      ),
    );

    // Get the currently selected formatter.
    if (isset($form_state['values']['options']['type'])) {
      $format = $form_state['values']['options']['type'];
    }
    else {
      $format = $this->options['type'];
    }
    $formatter = field_info_formatter_types($format);
    $settings = $this->options['settings'] + field_info_formatter_settings($format);

    // Provide an instance array for hook_field_formatter_settings_form().
    ctools_include('fields');
    $instance = ctools_fields_fake_field_instance($this->definition['field_name'], '_dummy', $formatter, $settings);

    // Store the settings in a '_dummy' view mode.
    $instance['display']['_dummy'] = array(
      'type' => $format,
      'settings' => $settings,
    );

    // Get the settings form.
    $settings_form = array('#value' => array());
    $function = $formatter['module'] . '_field_formatter_settings_form';
    if (function_exists($function)) {
      $settings_form = $function($field, $instance, '_dummy', $form, $form_state);
    }
    $form['settings'] = $settings_form;
  }

  /**
   * Extend the groupby form with group columns.
   */
  function groupby_form(&$form, &$form_state) {
    parent::groupby_form($form, $form_state);
    // With "field API" fields, the column target of the grouping function
    // and any additional grouping columns must be specified.
    $group_columns = drupal_map_assoc(array_keys($this->definition['field_info']['columns']), 'ucfirst');
    $form['group_column'] = array(
      '#type' => 'select',
      '#title' => t('Group column'),
      '#default_value' => $this->options['group_column'],
      '#description' => t('Select the column of this field to apply the grouping function selected above.'),
      '#options' => $group_columns,
    );

    $options = drupal_map_assoc(array('bundle', 'language', 'entity_type'), 'ucfirst');
    $form['group_columns'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Group columns (additional)'),
      '#default_value' => $this->options['group_columns'],
      '#description' => t('Select any additional columns of this field to include in the query and to group on.'),
      '#options' => $options,
    );
  }

  function groupby_form_submit(&$form, &$form_state) {
    parent::groupby_form_submit($form, $form_state);
    $item =& $form_state['handler']->options;

    if ($item['group_type'] != 'group') {
      unset($item['group_column']);
      unset($item['group_columns']);
    }
    else {
      // Add settings for "field API" fields.
      $item['group_column'] = $form_state['values']['group_column'];
      $item['group_columns'] = array_filter($form_state['values']['group_columns']);
    }
  }

  /**
   * Load the entities for all fields that are about to be displayed.
   */
  function pre_render(&$values) {
    if (!empty($values)) {
      // Divide the entity ids by entity type, so they can be loaded in bulk.
      $entities_by_type = array();
      foreach ($values as $key => $object) {
        if (isset($object->{$this->field_alias}) && !isset($values[$key]->_field_data[$this->field_alias])) {
          $entity_type = $object->{$this->aliases['entity_type']};
          $entity_id = $object->{$this->field_alias};
          $entities_by_type[$entity_type][$key] = $entity_id;
        }
      }

      // Load the entities.
      foreach ($entities_by_type as $entity_type => $entity_ids) {
        $entities = entity_load($entity_type, $entity_ids);

        foreach ($entity_ids as $key => $entity_id) {
          $values[$key]->_field_data[$this->field_alias] = array(
            'entity_type' => $entity_type,
            'entity' => $entities[$entity_id],
          );
        }
      }
    }
  }

  function render($values) {
    if (isset($values->_field_data[$this->field_alias])) {
      // Prepare arguments for rendering based on the objects cached in the
      // pre-render phase and the display options for this field.
      $entity_type = $values->_field_data[$this->field_alias]['entity_type'];
      $entity = $values->_field_data[$this->field_alias]['entity'];

      // The field we are trying to display doesn't exist on this entity.
      if (!isset($entity->{$this->definition['field_name']})) {
        return '';
      }

      $display = array(
        'type' => $this->options['type'],
        'settings' => $this->options['settings'],
        'label' => 'hidden',
      );

      $langcode = $this->field_language();
      $render_array = field_view_field($entity_type, $entity, $this->definition['field_name'], $display, $langcode);
      return drupal_render($render_array);
    }
    else {
      return '';
    }
  }

  /**
   * Return the language code of the language the field should be displayed in,
   * according to the settings.
   */
  function field_language() {
    global $language_content;

    if (field_is_translatable($this->entity_type, $this->definition['field_info'])) {
      $default_language = language_default('language');
      $language = str_replace(array('***CURRENT_LANGUAGE***', '***DEFAULT_LANGUAGE***'),
                              array($language_content->language, $default_language),
                              $this->query->options['field_language']);

      return $language;
    }
    else {
      return LANGUAGE_NONE;
    }
  }
}
