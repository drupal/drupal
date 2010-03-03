<?php
// $Id: field_ui.admin.inc,v 1.45 2010/03/03 08:01:42 dries Exp $

/**
 * @file
 * Administrative interface for custom field type creation.
 */

/**
 * Menu callback; lists all defined fields for quick reference.
 */
function field_ui_fields_list() {
  $instances = field_info_instances();
  $field_types = field_info_field_types();
  $bundles = field_info_bundles();
  $header = array(t('Field name'), t('Field type'), t('Used in'));
  $rows = array();
  foreach ($instances as $entity_type => $type_bundles) {
    foreach ($type_bundles as $bundle => $bundle_instances) {
      foreach ($bundle_instances as $field_name => $instance) {
        $field = field_info_field($field_name);
        $admin_path = _field_ui_bundle_admin_path($entity_type, $bundle);
        $rows[$field_name]['data'][0] = $field['locked'] ? t('@field_name (Locked)', array('@field_name' => $field_name)) : $field_name;
        $rows[$field_name]['data'][1] = t($field_types[$field['type']]['label']);
        $rows[$field_name]['data'][2][] = l($bundles[$entity_type][$bundle]['label'], $admin_path . '/fields');
        $rows[$field_name]['class'] = $field['locked'] ? array('menu-disabled') : array('');
      }
    }
  }
  foreach ($rows as $field_name => $cell) {
    $rows[$field_name]['data'][2] = implode(', ', $cell['data'][2]);
  }
  if (empty($rows)) {
    $output = t('No fields have been defined for any content type yet.');
  }
  else {
    // Sort rows by field name.
    ksort($rows);
    $output = theme('table', array('header' => $header, 'rows' => $rows));
  }
  return $output;
}

/**
 * Helper function to display a message about inactive fields.
 */
function field_ui_inactive_message($entity_type, $bundle) {
  $inactive_instances = field_ui_inactive_instances($entity_type, $bundle);
  if (!empty($inactive_instances)) {
    $field_types = field_info_field_types();
    $widget_types = field_info_widget_types();

    foreach ($inactive_instances as $field_name => $instance) {
      $list[] = t('%field (@field_name) field requires the %widget_type widget provided by %widget_module module', array(
      '%field' => $instance['label'],
      '@field_name' => $instance['field_name'],
      '%widget_type' => array_key_exists($instance['widget']['type'], $widget_types) ? $widget_types[$instance['widget']['type']]['label'] : $instance['widget']['type'],
      '%widget_module' => $instance['widget']['module'],
      ));
    }
    drupal_set_message(t('Inactive fields are not shown unless their providing modules are enabled. The following fields are not enabled: !list', array('!list' => theme('item_list', array('items' => $list)))), 'error');
  }
}

/**
 * Menu callback; listing of fields for a bundle.
 *
 * Allows fields and pseudo-fields to be re-ordered.
 */
function field_ui_field_overview_form($form, &$form_state, $entity_type, $bundle) {
  $bundle = field_extract_bundle($entity_type, $bundle);

  field_ui_inactive_message($entity_type, $bundle);
  $admin_path = _field_ui_bundle_admin_path($entity_type, $bundle);

  // When displaying the form, make sure the list of fields is up-to-date.
  if (empty($form_state['post'])) {
    field_info_cache_clear();
  }

  // Gather bundle information.
  $instances = field_info_instances($entity_type, $bundle);
  $field_types = field_info_field_types();
  $widget_types = field_info_widget_types();

  $extra = field_extra_fields($entity_type, $bundle);

  // Store each default weight so that we can add the 'add new' rows after them.
  $weights = array();

  $form += array(
    '#tree' => TRUE,
    '#object_type' => $entity_type,
    '#bundle' => $bundle,
    '#fields' => array_keys($instances),
    '#extra' => array_keys($extra),
    '#field_rows' => array(),
  );

  // Fields.
  foreach ($instances as $name => $instance) {
    $field = field_info_field($instance['field_name']);
    $admin_field_path = $admin_path . '/fields/' . $instance['field_name'];
    $weight = $instance['widget']['weight'];
    $form[$name] = array(
      'label' => array(
        '#markup' => check_plain($instance['label']),
      ),
      'field_name' => array(
        '#markup' => $instance['field_name'],
      ),
      'type' => array(
        '#type' => 'link',
        '#title' => t($field_types[$field['type']]['label']),
        '#href' => $admin_field_path . '/field-settings',
        '#options' => array('attributes' => array('title' => t('Edit field settings.'))),
      ),
      'widget_type' => array(
        '#type' => 'link',
        '#title' => t($widget_types[$instance['widget']['type']]['label']),
        '#href' => $admin_field_path . '/widget-type',
        '#options' => array('attributes' => array('title' => t('Change widget type.'))),
      ),
      'edit' => array(
        '#type' => 'link',
        '#title' => t('edit'),
        '#href' => $admin_field_path,
        '#options' => array('attributes' => array('title' => t('Edit instance settings.'))),
      ),
      'delete' => array(
        '#type' => 'link',
        '#title' => t('delete'),
        '#href' => $admin_field_path . '/delete',
        '#options' => array('attributes' => array('title' => t('Delete instance.'))),
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#default_value' => $weight,
        '#size' => 3,
       ),
      'hidden_name' => array(
        '#type' => 'hidden',
        '#default_value' => $instance['field_name'],
       ),
      '#row_type' => 'field',
    );

    if (!empty($instance['locked'])) {
      $form[$name]['edit'] = array('#value' => t('Locked'));
      $form[$name]['delete'] = array();
      $form[$name]['#disabled_row'] = TRUE;
    }
    $form['#field_rows'][] = $name;
    $weights[] = $weight;
  }

  // Non-field elements.
  foreach ($extra as $name => $label) {
    $weight = $extra[$name]['weight'];
    $form[$name] = array(
      'label' => array(
        '#markup' => t($extra[$name]['label']),
      ),
      'name' => array(
        '#markup' => $name,
      ),
      'description' => array(
        '#markup' => isset($extra[$name]['description']) ? $extra[$name]['description'] : '',
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#default_value' => $weight,
        '#size' => 3,
      ),
      'edit' => array(
        '#markup' => isset($extra[$name]['edit']) ? $extra[$name]['edit'] : '',
      ),
      'delete' => array(
        '#markup' => isset($extra[$name]['delete']) ? $extra[$name]['delete'] : '',
      ),
      'hidden_name' => array(
        '#type' => 'hidden',
        '#default_value' => $name,
      ),
      '#disabled_row' => TRUE,
      '#row_type' => 'extra',
    );
    $form['#field_rows'][] = $name;
    $weights[] = $weight;
  }

  // Additional row: add new field.
  $weight = !empty($weights) ? max($weights) + 1 : 0;
  $field_type_options = field_ui_field_type_options();
  $widget_type_options = field_ui_widget_type_options(NULL, TRUE);
  if ($field_type_options && $widget_type_options) {
    array_unshift($field_type_options, t('- Select a field type -'));
    array_unshift($widget_type_options, t('- Select a widget -'));
    $name = '_add_new_field';
    $form[$name] = array(
      'label' => array(
        '#type' => 'textfield',
        '#size' => 15,
        '#description' => t('Label'),
      ),
      'field_name' => array(
        '#type' => 'textfield',
        // This field should stay LTR even for RTL languages.
        '#field_prefix' => '<span dir="ltr">field_',
        '#field_suffix' => '</span>&lrm;',
        '#attributes' => array('dir'=>'ltr'),
        '#size' => 15,
        '#description' => t('Field name (a-z, 0-9, _)'),
      ),
      'type' => array(
        '#type' => 'select',
        '#options' => $field_type_options,
        '#description' => t('Type of data to store.'),
      ),
      'widget_type' => array(
        '#type' => 'select',
        '#options' => $widget_type_options,
        '#description' => t('Form element to edit the data.'),
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#default_value' => $weight,
        '#size' => 3,
      ),
      'hidden_name' => array(
        '#type' => 'hidden',
        '#default_value' => $name,
      ),
      '#add_new' => TRUE,
      '#row_type' => 'add_new_field',
    );
    $form['#field_rows'][] = $name;
  }

  // Additional row: add existing field.
  $existing_field_options = field_ui_existing_field_options($entity_type, $bundle);
  if ($existing_field_options && $widget_type_options) {
    $weight++;
    array_unshift($existing_field_options, t('- Select an existing field -'));
    $name = '_add_existing_field';
    $form[$name] = array(
      'label' => array(
        '#type' => 'textfield',
        '#size' => 15,
        '#description' => t('Label'),
      ),
      'field_name' => array(
        '#type' => 'select',
        '#options' => $existing_field_options,
        '#description' => t('Field to share'),
      ),
      'widget_type' => array(
        '#type' => 'select',
        '#options' => $widget_type_options,
        '#description' => t('Form element to edit the data.'),
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#default_value' => $weight,
        '#size' => 3,
      ),
      'hidden_name' => array(
        '#type' => 'hidden',
        '#default_value' => $name,
      ),
      '#add_new' => TRUE,
      '#row_type' => 'add_existing_field',
    );
    $form['#field_rows'][] = $name;
  }

  $form['submit'] = array('#type' => 'submit', '#value' => t('Save'));
  return $form;
}

/**
 * Theme preprocess function for field_ui-field-overview-form.tpl.php.
 */
function template_preprocess_field_ui_field_overview_form(&$vars) {
  $form = &$vars['form'];

  drupal_add_css(drupal_get_path('module', 'field_ui') . '/field_ui.css');
  drupal_add_tabledrag('field-overview', 'order', 'sibling', 'field-weight');
  drupal_add_js(drupal_get_path('module', 'field_ui') . '/field_ui.js');
  // Add settings for the update selects behavior.
  $js_fields = array();
  foreach (field_ui_existing_field_options($form['#object_type'], $form['#bundle']) as $field_name => $fields) {
    $field = field_info_field($field_name);
    $instance = field_info_instance($form['#object_type'], $field_name, $form['#bundle']);
    $js_fields[$field_name] = array('label' => $instance['label'], 'type' => $field['type'], 'widget' => $instance['widget']['type']);
  }
  drupal_add_js(array('fieldWidgetTypes' => field_ui_widget_type_options(), 'fields' => $js_fields), 'setting');

  $order = _field_ui_overview_order($form, $form['#field_rows']);
  $rows = array();

  // Identify the 'new item' keys in the form.
  $keys = array_keys($form);
  $add_rows = array();
  foreach ($keys as $key) {
    if (substr($key, 0, 4) == '_add') {
      $add_rows[] = $key;
    }
  }
  while ($order) {
    $key = reset($order);
    $element = &$form[$key];
    $row = new stdClass();

    // Add target classes for the tabledrag behavior.
    $element['weight']['#attributes']['class'][] = 'field-weight';
    $element['hidden_name']['#attributes']['class'][] = 'field-name';
    // Add target classes for the update selects behavior.
    switch ($element['#row_type']) {
      case 'add_new_field':
        $element['type']['#attributes']['class'][] = 'field-type-select';
        $element['widget_type']['#attributes']['class'][] = 'widget-type-select';
        break;

      case 'add_existing_field':
        $element['field_name']['#attributes']['class'][] = 'field-select';
        $element['widget_type']['#attributes']['class'][] = 'widget-type-select';
        $element['label']['#attributes']['class'][] = 'label-textfield';
        break;
    }
    foreach (element_children($element) as $child) {
      $row->{$child} = drupal_render($element[$child]);
    }
    $row->label_class = 'label-' . strtr($element['#row_type'], '_', '-');
    $row->row_type = $element['#row_type'];
    $row->class = 'draggable';
    $row->class .= isset($element['#add_new']) ? ' add-new' : '';
    $row->class .= isset($element['#disabled_row']) ? ' menu-disabled' : '';

    $rows[] = $row;
    array_shift($order);
  }
  $vars['rows'] = $rows;
  $vars['submit'] = drupal_render_children($form);
}

/**
 * Validate handler for the field overview form.
 */
function field_ui_field_overview_form_validate($form, &$form_state) {
  _field_ui_field_overview_form_validate_add_new($form, $form_state);
  _field_ui_field_overview_form_validate_add_existing($form, $form_state);
}

/**
 * Helper function for field_ui_field_overview_form_validate.
 *
 * Validate the 'add new field' row.
 */
function _field_ui_field_overview_form_validate_add_new($form, &$form_state) {
  $field = $form_state['values']['_add_new_field'];

  // Validate if any information was provided in the 'add new field' row.
  if (array_filter(array($field['label'], $field['field_name'], $field['type'], $field['widget_type']))) {
    // Missing label.
    if (!$field['label']) {
      form_set_error('_add_new_field][label', t('Add new field: you need to provide a label.'));
    }

    // Missing field name.
    if (!$field['field_name']) {
      form_set_error('_add_new_field][field_name', t('Add new field: you need to provide a field name.'));
    }
    // Field name validation.
    else {
      $field_name = $field['field_name'];

      // Add the 'field_' prefix.
      if (substr($field_name, 0, 6) != 'field_') {
        $field_name = 'field_' . $field_name;
        form_set_value($form['_add_new_field']['field_name'], $field_name, $form_state);
      }

      // Invalid field name.
      if (!preg_match('!^field_[a-z0-9_]+$!', $field_name)) {
        form_set_error('_add_new_field][field_name', t('Add new field: the field name %field_name is invalid. The name must include only lowercase unaccentuated letters, numbers, and underscores.', array('%field_name' => $field_name)));
      }
      if (strlen($field_name) > 32) {
        form_set_error('_add_new_field][field_name', t("Add new field: the field name %field_name is too long. The name is limited to 32 characters, including the 'field_' prefix.", array('%field_name' => $field_name)));
      }

      // Field name already exists. We need to check inactive fields as well, so
      // we can't use field_info_fields().
      $fields = field_read_fields(array('field_name' => $field_name), array('include_inactive' => TRUE));
      if ($fields) {
        form_set_error('_add_new_field][field_name', t('Add new field: the field name %field_name already exists.', array('%field_name' => $field_name)));
      }
    }

    // Missing field type.
    if (!$field['type']) {
      form_set_error('_add_new_field][type', t('Add new field: you need to select a field type.'));
    }

    // Missing widget type.
    if (!$field['widget_type']) {
      form_set_error('_add_new_field][widget_type', t('Add new field: you need to select a widget.'));
    }
    // Wrong widget type.
    elseif ($field['type']) {
      $widget_types = field_ui_widget_type_options($field['type']);
      if (!isset($widget_types[$field['widget_type']])) {
        form_set_error('_add_new_field][widget_type', t('Add new field: invalid widget.'));
      }
    }
  }
}

/**
 * Helper function for field_ui_field_overview_form_validate.
 *
 * Validate the 'add existing field' row.
 */
function _field_ui_field_overview_form_validate_add_existing($form, &$form_state) {
  // The form element might be absent if no existing fields can be added to
  // this content type
  if (isset($form_state['values']['_add_existing_field'])) {
    $field = $form_state['values']['_add_existing_field'];

    // Validate if any information was provided in the 'add existing field' row.
    if (array_filter(array($field['label'], $field['field_name'], $field['widget_type']))) {
      // Missing label.
      if (!$field['label']) {
        form_set_error('_add_existing_field][label', t('Add existing field: you need to provide a label.'));
      }

      // Missing existing field name.
      if (!$field['field_name']) {
        form_set_error('_add_existing_field][field_name', t('Add existing field: you need to select a field.'));
      }

      // Missing widget type.
      if (!$field['widget_type']) {
        form_set_error('_add_existing_field][widget_type', t('Add existing field: you need to select a widget.'));
      }
      // Wrong widget type.
      elseif ($field['field_name'] && ($existing_field = field_info_field($field['field_name']))) {
        $widget_types = field_ui_widget_type_options($existing_field['type']);
        if (!isset($widget_types[$field['widget_type']])) {
          form_set_error('_add_existing_field][widget_type', t('Add existing field: invalid widget.'));
        }
      }
    }
  }
}

/**
 * Submit handler for the field overview form.
 */
function field_ui_field_overview_form_submit($form, &$form_state) {
  $form_values = $form_state['values'];
  $entity_type = $form['#object_type'];
  $bundle = $form['#bundle'];
  $admin_path = _field_ui_bundle_admin_path($entity_type, $bundle);

  // Update field weights.
  $extra = array();
  foreach ($form_values as $key => $values) {
    if (in_array($key, $form['#fields'])) {
      $instance = field_read_instance($entity_type, $key, $bundle);
      $instance['widget']['weight'] = $values['weight'];
      foreach($instance['display'] as $view_mode => $display) {
        $instance['display'][$view_mode]['weight'] = $values['weight'];
      }
      field_update_instance($instance);
    }
    elseif (in_array($key, $form['#extra'])) {
      $extra[$key] = $values['weight'];
    }
  }

  $extra_weights = variable_get('field_extra_weights', array());
  $extra_weights[$entity_type][$bundle] = $extra;
  variable_set('field_extra_weights', $extra_weights);

  $destinations = array();

  // Create new field.
  $field = array();
  if (!empty($form_values['_add_new_field']['field_name'])) {
    $values = $form_values['_add_new_field'];

    $field = array(
      'field_name' => $values['field_name'],
      'type' => $values['type'],
      'translatable' => TRUE,
    );
    $instance = array(
      'field_name' => $field['field_name'],
      'object_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $values['label'],
      'widget' => array(
        'type' => $values['widget_type'],
        'weight' => $values['weight'],
      ),
    );

    // Create the field and instance.
    try {
      field_create_field($field);
      field_create_instance($instance);

      $destinations[] = $admin_path . '/fields/' . $field['field_name'] . '/field-settings';
      $destinations[] = $admin_path . '/fields/' . $field['field_name'] . '/edit';

      // Store new field information for any additional submit handlers.
      $form_state['fields_added']['_add_new_field'] = $field['field_name'];
    }
    catch (Exception $e) {
      drupal_set_message(t('There was a problem creating field %label: @message.', array('%label' => $instance['label'], '@message' => $e->getMessage())));
    }
  }

  // Add existing field.
  if (!empty($form_values['_add_existing_field']['field_name'])) {
    $values = $form_values['_add_existing_field'];
    $field = field_info_field($values['field_name']);
    if (!empty($field['locked'])) {
      drupal_set_message(t('The field %label cannot be added because it is locked.', array('%label' => $values['label'])));
    }
    else {
      $instance = array(
        'field_name' => $field['field_name'],
        'object_type' => $entity_type,
        'bundle' => $bundle,
        'label' => $values['label'],
        'widget' => array(
          'type' => $values['widget_type'],
          'weight' => $values['weight'],
        ),
      );

      try {
        field_create_instance($instance);
        $destinations[] = $admin_path . '/fields/' . $instance['field_name'] . '/edit';
        // Store new field information for any additional submit handlers.
        $form_state['fields_added']['_add_existing_field'] = $instance['field_name'];
      }
      catch (Exception $e) {
        drupal_set_message(t('There was a problem creating field instance %label: @message.', array('%label' => $instance['label'], '@message' => $e->getMessage())));
      }
    }
  }

  if ($destinations) {
    $destination = drupal_get_destination();
    $destinations[] = $destination['destination'];
    unset($_GET['destination']);
    $form_state['redirect'] = field_ui_get_destinations($destinations);
  }
}

/**
 * Menu callback; presents a listing of fields display settings for a bundle.
 *
 * This form includes form widgets to select which fields appear in teaser and
 * full view modes, and how the field labels should be rendered.
 */
function field_ui_display_overview_form($form, &$form_state, $entity_type, $bundle, $view_modes_selector = 'basic') {
  $bundle = field_extract_bundle($entity_type, $bundle);

  field_ui_inactive_message($entity_type, $bundle);
  $admin_path = _field_ui_bundle_admin_path($entity_type, $bundle);

  // Gather type information.
  $instances = field_info_instances($entity_type, $bundle);
  $field_types = field_info_field_types();
  $view_modes = field_ui_view_modes_tabs($entity_type, $view_modes_selector);

  $form += array(
    '#tree' => TRUE,
    '#object_type' => $entity_type,
    '#bundle' => $bundle,
    '#fields' => array_keys($instances),
    '#contexts' => $view_modes_selector,
  );

  if (empty($instances)) {
    drupal_set_message(t('There are no fields yet added. You can add new fields on the <a href="@link">Manage fields</a> page.', array('@link' => url($admin_path . '/fields'))), 'warning');
    return $form;
  }

  // Fields.
  $label_options = array(
    'above' => t('Above'),
    'inline' => t('Inline'),
    'hidden' => t('<Hidden>'),
  );
  foreach ($instances as $name => $instance) {
    $field = field_info_field($instance['field_name']);
    $weight = $instance['widget']['weight'];

    $form[$name] = array(
      'human_name' => array('#markup' => check_plain($instance['label'])),
      'weight' => array('#type' => 'value', '#value' => $weight),
    );
    $defaults = $instance['display'];

    $formatter_options = field_ui_formatter_options($field['type']);
    $formatter_options['hidden'] = t('<Hidden>');
    foreach ($view_modes as $view_mode) {
      $display = isset($instance['display'][$view_mode]) ? $instance['display'][$view_mode] : $instance['display']['full'];
      $form[$name][$view_mode]['label'] = array(
        '#type' => 'select',
        '#options' => $label_options,
        '#default_value' => $display['label'],
      );
      $form[$name][$view_mode]['type'] = array(
        '#type' => 'select',
        '#options' => $formatter_options,
        '#default_value' => $display['type'],
      );
    }
  }

  $form['submit'] = array('#type' => 'submit', '#value' => t('Save'));
  return $form;
}

/**
 * Theme preprocess function for field_ui-display-overview-form.tpl.php.
 */
function template_preprocess_field_ui_display_overview_form(&$vars) {
  $form = &$vars['form'];

  $contexts_selector = $form['#contexts'];
  $view_modes = field_ui_view_modes_tabs($form['#object_type'], $contexts_selector);
  $entity_info = entity_get_info($form['#object_type']);
  $view_modes_info = $entity_info['view modes'];
  $vars['contexts'] = array();
  foreach ($view_modes as $view_mode) {
    $vars['contexts'][$view_mode] = $view_modes_info[$view_mode]['label'];
  }

  $order = _field_ui_overview_order($form, $form['#fields']);
  if (empty($order)) {
    $vars['rows'] = array();
    $vars['submit'] = '';
    return;
  }
  $rows = array();
  foreach ($order as $key) {
    $element = &$form[$key];
    $row = new stdClass();
    foreach (element_children($element) as $child) {
      if (array_key_exists('label', $element[$child])) {
        $row->{$child}->label = drupal_render($element[$child]['label']);
        $row->{$child}->type = drupal_render($element[$child]['type']);
      }
      else {
        $row->{$child} = drupal_render($element[$child]);
      }
    }
    $row->label_class = 'label-field';
    $rows[] = $row;
  }

  $vars['rows'] = $rows;
  $vars['submit'] = drupal_render_children($form);
}

/**
 * Submit handler for the display overview form.
 */
function field_ui_display_overview_form_submit($form, &$form_state) {
  $form_values = $form_state['values'];
  foreach ($form_values as $key => $values) {
    if (in_array($key, $form['#fields'])) {
      $instance = field_info_instance($form['#object_type'], $key, $form['#bundle']);
      foreach ($instance['display'] as $view_mode => $display) {
        if (isset($values[$view_mode])) {
          $instance['display'][$view_mode] = array_merge($instance['display'][$view_mode], $values[$view_mode]);
        }
      }
      field_update_instance($instance);
    }
  }
  drupal_set_message(t('Your settings have been saved.'));
}

/**
 * Return an array of field_type options.
 */
function field_ui_field_type_options() {
  $options = &drupal_static(__FUNCTION__);

  if (!isset($options)) {
    $options = array();
    $field_types = field_info_field_types();
    $field_type_options = array();
    foreach ($field_types as $name => $field_type) {
      // Skip field types which have no widget types.
      if (field_ui_widget_type_options($name)) {
        $options[$name] = $field_type['label'];
      }
    }
    asort($options);
  }
  return $options;
}

/**
 * Return an array of widget type options for a field type.
 *
 * If no field type is provided, returns a nested array of all widget types,
 * keyed by field type human name.
 */
function field_ui_widget_type_options($field_type = NULL, $by_label = FALSE) {
  $options = &drupal_static(__FUNCTION__);

  if (!isset($options)) {
    $options = array();
    $field_types = field_info_field_types();
    foreach (field_info_widget_types() as $name => $widget_type) {
      foreach ($widget_type['field types'] as $widget_field_type) {
        // Check that the field type exists.
        if (isset($field_types[$widget_field_type])) {
          $options[$widget_field_type][$name] = $widget_type['label'];
        }
      }
    }
  }

  if (isset($field_type)) {
    return !empty($options[$field_type]) ? $options[$field_type] : array();
  }
  if ($by_label) {
    $field_types = field_info_field_types();
    $options_by_label = array();
    foreach ($options as $field_type => $widgets) {
      $options_by_label[$field_types[$field_type]['label']] = $widgets;
    }
    return $options_by_label;
  }
  return $options;
}

/**
 * Return an array of formatter options for a field type.
 *
 * If no field type is provided, returns a nested array of all formatters, keyed
 * by field type.
 */
function field_ui_formatter_options($field_type = NULL) {
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
 * Return an array of existing field to be added to a bundle.
 */
function field_ui_existing_field_options($entity_type, $bundle) {
  $options = array();
  $field_types = field_info_field_types();

  foreach (field_info_instances() as $existing_entity_type => $bundles) {
    foreach ($bundles as $existing_bundle => $instances) {
      // No need to look in the current bundle.
      if (!($existing_bundle == $bundle && $existing_entity_type == $entity_type)) {
        foreach ($instances as $instance) {
          $field = field_info_field($instance['field_name']);
          // Don't show
          // - locked fields,
          // - fields already in the current bundle,
          // - field that cannot be added to the entity type.
          if (empty($field['locked'])
            && !field_info_instance($entity_type, $field['field_name'], $bundle)
            && (empty($field['object_types']) || in_array($entity_type, $field['object_types']))) {
            $text = t('@type: @field (@label)', array(
              '@type' => $field_types[$field['type']]['label'],
              '@label' => t($instance['label']), '@field' => $instance['field_name'],
            ));
            $options[$instance['field_name']] = (drupal_strlen($text) > 80 ? truncate_utf8($text, 77) . '...' : $text);
          }
        }
      }
    }
  }
  // Sort the list by field name.
  asort($options);
  return $options;
}

/**
 * Menu callback; presents the field settings edit page.
 */
function field_ui_field_settings_form($form, &$form_state, $entity_type, $bundle, $field) {
  $bundle = field_extract_bundle($entity_type, $bundle);
  $instance = field_info_instance($entity_type, $field['field_name'], $bundle);

  // When a field is first created, we have to get data from the db.
  if (!isset($instance['label'])) {
    $instance = field_read_instance($field['field_name'], $bundle);
    $field = field_read_field($field['field_name']);
  }

  drupal_set_title($instance['label']);

  $description = '<p>' . t('These settings apply to the %field field everywhere it is used. These settings impact the way that data is stored in the database and cannot be changed once data has been created.', array('%field' => $instance['label'])) . '</p>';

  // Create a form structure for the field values.
  $form['field'] = array(
    '#type' => 'fieldset',
    '#title' => t('Field settings'),
    '#description' => $description,
    '#tree' => TRUE,
  );

  // See if data already exists for this field.
  // If so, prevent changes to the field settings.
  $has_data = field_has_data($field);
  if ($has_data) {
    $form['field']['#description'] = '<div class=error>' . t('There is data for this field in the database. The field settings can no longer be changed.' . '</div>') . $form['field']['#description'];
  }

  // Build the non-configurable field values.
  $form['field']['field_name'] = array('#type' => 'value', '#value' => $field['field_name']);
  $form['field']['type'] = array('#type' => 'value', '#value' => $field['type']);
  $form['field']['module'] = array('#type' => 'value', '#value' => $field['module']);
  $form['field']['active'] = array('#type' => 'value', '#value' => $field['active']);

  // Add settings provided by the field module. The field module is
  // responsible for not returning settings that cannot be changed if
  // the field already has data.
  $form['field']['settings'] = array();
  $additions = module_invoke($field['module'], 'field_settings_form', $field, $instance, $has_data);
  if (is_array($additions)) {
    $form['field']['settings'] = $additions;
  }
  if (empty($form['field']['settings'])) {
    $form['field']['settings'] = array(
      '#markup' => t('%field has no field settings.', array('%field' => $instance['label'])),
    );
  }
  $form['#object_type'] = $entity_type;
  $form['#bundle'] = $bundle;

  $form['actions'] = array('#type' => 'container', '#attributes' => array('class' => array('form-actions')));
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save field settings'));
  return $form;
}

/**
 * Save a field's settings after editing.
 */
function field_ui_field_settings_form_submit($form, &$form_state) {
  $form_values = $form_state['values'];
  $field_values = $form_values['field'];

  // Merge incoming form values into the existing field.
  $field = field_info_field($field_values['field_name']);

  $entity_type = $form['#object_type'];
  $bundle = $form['#bundle'];
  $instance = field_info_instance($entity_type, $field['field_name'], $bundle);

  // Update the field.
  $field = array_merge($field, $field_values);

  try {
    field_update_field($field);
    drupal_set_message(t('Updated field %label field settings.', array('%label' => $instance['label'])));
    $form_state['redirect'] = field_ui_next_destination($entity_type, $bundle);
  }
  catch (FieldException $e) {
    drupal_set_message(t('Attempt to update field %label failed: %message.', array('%label' => $instance['label'], '%message' => $e->getMessage())), 'error');
    // TODO: Where do we go from here?
    $form_state['redirect'] = field_ui_next_destination($entity_type, $bundle);
  }
}

/**
 * Menu callback; select a widget for the field.
 */
function field_ui_widget_type_form($form, &$form_state, $entity_type, $bundle, $field) {
  $bundle = field_extract_bundle($entity_type, $bundle);
  $instance = field_info_instance($entity_type, $field['field_name'], $bundle);

  drupal_set_title($instance['label']);

  $field_type = field_info_field_types($field['type']);
  $widget_type = field_info_widget_types($instance['widget']['type']);
  $bundles = field_info_bundles();
  $bundle_label = $bundles[$entity_type][$bundle]['label'];

  $form['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Change widget'),
  );
  $form['basic']['widget_type'] = array(
    '#type' => 'select',
    '#title' => t('Widget type'),
    '#required' => TRUE,
    '#options' => field_ui_widget_type_options($field['type']),
    '#default_value' => $instance['widget']['type'],
    '#description' => t('The type of form element you would like to present to the user when creating this field in the %type type.', array('%type' => $bundle_label)),
  );

  $form['#instance'] = $instance;
  $form['submit'] = array('#type' => 'submit', '#value' => t('Continue'));

  $form['#validate'] = array();
  $form['#submit'] = array('field_ui_widget_type_form_submit');

  return $form;
}

/**
 * Submit the change in widget type.
 */
function field_ui_widget_type_form_submit($form, &$form_state) {
  $form_values = $form_state['values'];
  $instance = $form['#instance'];
  $bundle = $instance['bundle'];
  $entity_type = $instance['object_type'];

  // Set the right module information.
  $widget_type = field_info_widget_types($form_values['widget_type']);
  $widget_module = $widget_type['module'];

  $instance['widget']['type'] = $form_values['widget_type'];
  $instance['widget']['module'] = $widget_module;
  try {
    field_update_instance($instance);
    drupal_set_message(t('Changed the widget for field %label.', array('%label' => $instance['label'])));
  }
  catch (FieldException $e) {
    drupal_set_message(t('There was a problem changing the widget for field %label.', array('%label' => $instance['label'])));
  }

  $form_state['redirect'] = field_ui_next_destination($entity_type, $bundle);
}

/**
 * Menu callback; present a form for removing a field from a content type.
 */
function field_ui_field_delete_form($form, &$form_state, $entity_type, $bundle, $field) {
  $bundle = field_extract_bundle($entity_type, $bundle);
  $instance = field_info_instance($entity_type, $field['field_name'], $bundle);
  $admin_path = _field_ui_bundle_admin_path($entity_type, $bundle);

  $form['object_type'] = array('#type' => 'value', '#value' => $entity_type);
  $form['bundle'] = array('#type' => 'value', '#value' => $bundle);
  $form['field_name'] = array('#type' => 'value', '#value' => $field['field_name']);

  $output = confirm_form($form,
    t('Are you sure you want to delete the field %field?', array('%field' => $instance['label'])),
    $admin_path . '/fields',
    t('If you have any content left in this field, it will be lost. This action cannot be undone.'),
    t('Delete'), t('Cancel'),
    'confirm'
  );

  if ($field['locked']) {
    unset($output['actions']['submit']);
    $output['description']['#markup'] = t('This field is <strong>locked</strong> and cannot be deleted.');
  }

  return $output;
}

/**
 * Remove a field from a content type.
 */
function field_ui_field_delete_form_submit($form, &$form_state) {
  $form_values = $form_state['values'];
  $field_name = $form_values['field_name'];
  $bundle = $form_values['bundle'];
  $entity_type = $form_values['object_type'];

  $field = field_info_field($field_name);
  $instance = field_info_instance($entity_type, $field_name, $bundle);
  $bundles = field_info_bundles();
  $bundle_label = $bundles[$entity_type][$bundle]['label'];

  if (!empty($bundle) && $field && !$field['locked'] && $form_values['confirm']) {
    field_delete_instance($instance);
    // Delete the field if that was the last instance.
    if (count($field['bundles']) == 1 && count(current($field['bundles'])) == 1) {
      field_delete_field($field['field_name']);
    }
    drupal_set_message(t('The field %field has been deleted from the %type content type.', array('%field' => $instance['label'], '%type' => $bundle_label)));
  }
  else {
    drupal_set_message(t('There was a problem removing the %field from the %type content type.', array('%field' => $instance['label'], '%type' => $bundle_label)));
  }

  $admin_path = _field_ui_bundle_admin_path($entity_type, $bundle);
  $form_state['redirect'] = field_ui_get_destinations(array($admin_path . '/fields'));
}

/**
 * Menu callback; presents the field instance edit page.
 */
function field_ui_field_edit_form($form, &$form_state, $entity_type, $bundle, $field) {
  $bundle = field_extract_bundle($entity_type, $bundle);
  $instance = field_info_instance($entity_type, $field['field_name'], $bundle);

  $form['#field'] = $field;

  if (!empty($field['locked'])) {
    $form['locked'] = array(
      '#markup' => t('The field %field is locked and cannot be edited.', array('%field' => $instance['label'])),
    );
    return $form;
  }

  $field_type = field_info_field_types($field['type']);
  $widget_type = field_info_widget_types($instance['widget']['type']);
  $bundles = field_info_bundles();

  $title = isset($instance['label']) ? $instance['label'] : $instance['field_name'];
  drupal_set_title(check_plain($title));

  // Create a form structure for the instance values.
  $form['instance'] = array(
    '#tree' => TRUE,
    '#type' => 'fieldset',
    '#title' => t('%type settings', array('%type' => $bundles[$entity_type][$bundle]['label'])),
    '#description' => t('These settings apply only to the %field field when used in the %type type.', array('%field' => $instance['label'], '%type' => $bundles[$entity_type][$bundle]['label'])),
    '#pre_render' => array('field_ui_field_edit_instance_pre_render'),
  );

  // Build the non-configurable instance values.
  $form['instance']['field_name'] = array(
    '#type' => 'value',
    '#value' => $instance['field_name'],
  );
  $form['instance']['object_type'] = array(
    '#type' => 'value',
    '#value' => $entity_type,
  );
  $form['instance']['bundle'] = array(
    '#type' => 'value',
    '#value' => $bundle,
  );
  $form['instance']['widget']['weight'] = array(
    '#type' => 'value',
    '#value' => !empty($instance['widget']['weight']) ? $instance['widget']['weight'] : 0,
  );

  // Build the configurable instance values.
  $form['instance']['label'] = array(
    '#type' => 'textfield',
    '#title' => t('Label'),
    '#default_value' => !empty($instance['label']) ? $instance['label'] : $field['field_name'],
    '#required' => TRUE,
    '#weight' => -20,
  );
  $form['instance']['required'] = array(
    '#type' => 'checkbox',
    '#title' => t('Required field'),
    '#default_value' => !empty($instance['required']),
    '#weight' => -10,
  );

  $form['instance']['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Help text'),
    '#default_value' => !empty($instance['description']) ? $instance['description'] : '',
    '#rows' => 5,
    '#description' => t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', array('@tags' => _field_filter_xss_display_allowed_tags())),
    '#required' => FALSE,
    '#weight' => 0,
  );

  // Build the widget component of the instance.
  $form['instance']['widget']['type'] = array(
    '#type' => 'value',
    '#value' => $instance['widget']['type'],
  );
  $form['instance']['widget']['module'] = array(
    '#type' => 'value',
    '#value' => $widget_type['module'],
  );
  $form['instance']['widget']['active'] = array(
    '#type' => 'value',
    '#value' => !empty($field['instance']['widget']['active']) ? 1 : 0,
  );

  // Add additional field instance settings from the field module.
  $additions = module_invoke($field['module'], 'field_instance_settings_form', $field, $instance);
  if (is_array($additions)) {
    $form['instance']['settings'] = $additions;
  }

  // Add additional widget settings from the widget module.
  $additions = module_invoke($widget_type['module'], 'field_widget_settings_form', $field, $instance);
  if (is_array($additions)) {
    $form['instance']['widget']['settings'] = $additions;
    $form['instance']['widget']['active']['#value'] = 1;
  }

  // Add handling for default value if not provided by any other module.
  if (field_behaviors_widget('default value', $instance) == FIELD_BEHAVIOR_DEFAULT && empty($instance['default_value_function'])) {
    $form['instance']['default_value_widget'] = field_ui_default_value_widget($field, $instance, $form, $form_state);
  }

  $has_data = field_has_data($field);
  if ($has_data) {
    $description = '<p>' . t('These settings apply to the %field field everywhere it is used. Because the field already has data, some settings can no longer be changed.', array('%field' => $instance['label'])) . '</p>';
  }
  else {
    $description = '<p>' . t('These settings apply to the %field field everywhere it is used.', array('%field' => $instance['label'])) . '</p>';
  }

  // Create a form structure for the field values.
  $form['field'] = array(
    '#type' => 'fieldset',
    '#title' => t('%field field settings', array('%field' => $instance['label'])),
    '#description' => $description,
    '#tree' => TRUE,
  );

  // Build the configurable field values.
  $description = t('Maximum number of values users can enter for this field.');
  if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_DEFAULT) {
    $description .= '<br/>' . t("'Unlimited' will provide an 'Add more' button so the users can add as many values as they like.");
  }
  $form['field']['cardinality'] = array(
    '#type' => 'select',
    '#title' => t('Number of values'),
    '#options' => array(FIELD_CARDINALITY_UNLIMITED => t('Unlimited')) + drupal_map_assoc(range(1, 10)),
    '#default_value' => $field['cardinality'],
    '#description' => $description,
  );

  // Add additional field type settings. The field type module is
  // responsible for not returning settings that cannot be changed if
  // the field already has data.
  $additions = module_invoke($field['module'], 'field_settings_form', $field, $instance, $has_data);
  if (is_array($additions)) {
    $form['field']['settings'] = $additions;
  }

  $form['submit'] = array('#type' => 'submit', '#value' => t('Save settings'));
  return $form;
}

/**
 * Pre-render function for field instance settings.
 *
 * Combines the instance, widget, and other settings into a single fieldset so
 * that elements within each group can be shown at different weights as if they
 * all had the same parent.
 */
function field_ui_field_edit_instance_pre_render($element) {
  // Merge the widget settings into the main form.
  if (isset($element['widget']['settings'])) {
    foreach (element_children($element['widget']['settings']) as $key) {
      $element['widget_' . $key] = $element['widget']['settings'][$key];
    }
    unset($element['widget']['settings']);
  }

  // Merge the instance settings into the main form.
  if (isset($element['settings'])) {
    foreach (element_children($element['settings']) as $key) {
      $element['instance_' . $key] = $element['settings'][$key];
    }
    unset($element['settings']);
  }

  return $element;
}

/**
 * Build default value fieldset.
 */
function field_ui_default_value_widget($field, $instance, &$form, &$form_state) {
  $field_name = $field['field_name'];

  $element = array(
    '#type' => 'fieldset',
    '#title' => t('Default value'),
    '#collapsible' => FALSE,
    '#tree' => TRUE,
    '#description' => t('The default value for this field, used when creating new content.'),
    // Make sure the values appear at the top-level of $form_state['values'].
    '#parents' => array(),
  );

  // Insert the widget.
  $items = $instance['default_value'];
  $instance['required'] = FALSE;
  $instance['description'] = '';
  // @todo Allow multiple values (requires more work on 'add more' JS handler).
  $element += field_default_form(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $form, $form_state, 0);

  return $element;
}

/**
 * Form validation handler for field instance settings form.
 */
function field_ui_field_edit_form_validate($form, &$form_state) {
  $instance = $form_state['values']['instance'];
  $field_name = $instance['field_name'];
  $field = field_info_field($field_name);

  // Extract the 'default value'.
  $items = array();
  field_default_extract_form_values(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $form, $form_state);
  // Validate the value and report errors.
  $errors = array();
  $function = $field['module'] . '_field_validate';
  if (function_exists($function)) {
    $function(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $errors);
  }
  if (isset($errors[$field_name][LANGUAGE_NONE])) {
    $form_state['field'][$field_name][LANGUAGE_NONE]['errors'] = $errors[$field_name][LANGUAGE_NONE];
    field_default_form_errors(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $form, $form_state);
  }
}

/**
 * Form submit handler for field instance settings form.
 */
function field_ui_field_edit_form_submit($form, &$form_state) {
  $instance = $form_state['values']['instance'];
  $field = $form_state['values']['field'];

  // Update any field settings that have changed.
  $field_source = field_info_field($instance['field_name']);
  $field = array_merge($field_source, $field);
  field_update_field($field);

  // Handle the default value.
  // Extract field values.
  $items = array();
  field_default_extract_form_values(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $form, $form_state);
  // Prepare field values.
  field_default_submit(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $form, $form_state);
  $instance['default_value'] = $items ? $items : NULL;

  // Update the instance settings.
  $instance_source = field_info_instance($instance['object_type'], $instance['field_name'], $instance['bundle']);
  $instance = array_merge($instance_source, $instance);
  field_update_instance($instance);

  drupal_set_message(t('Saved %label configuration.', array('%label' => $instance['label'])));

  $form_state['redirect'] = field_ui_next_destination($instance['object_type'], $instance['bundle']);
}

/**
 * Helper functions to handle multipage redirects.
 */
function field_ui_get_destinations($destinations) {
  $query = array();
  $path = array_shift($destinations);
  if ($destinations) {
    $query['destinations'] = $destinations;
  }
  return array($path, array('query' => $query));
}

/**
 * Return the next redirect path in a multipage sequence.
 */
function field_ui_next_destination($entity_type, $bundle) {
  $destinations = !empty($_REQUEST['destinations']) ? $_REQUEST['destinations'] : array();
  if (!empty($destinations)) {
    unset($_REQUEST['destinations']);
    return field_ui_get_destinations($destinations);
  }
  $admin_path = _field_ui_bundle_admin_path($entity_type, $bundle);
  return $admin_path . '/fields';
}

/**
 * Helper function to order fields when theming overview forms.
 */
function _field_ui_overview_order(&$form, $field_rows) {
  // Put weight and parenting values into a $dummy render structure and let
  // drupal_render() figure out the corresponding row order.
  $dummy = array();
  // Field rows: account for weight.
  foreach ($field_rows as $name) {
    $dummy[$name] = array(
      '#markup' => $name . ' ',
      '#type' => 'markup',
      '#weight' => $form[$name]['weight']['#value'],
    );
  }
  return $dummy ? explode(' ', trim(drupal_render($dummy))) : array();
}

/**
 * Helper form element validator: integer.
 */
function _element_validate_integer($element, &$form_state) {
  $value = $element['#value'];
  if ($value !== '' && (!is_numeric($value) || intval($value) != $value)) {
    form_error($element, t('%name must be an integer.', array('%name' => $element['#title'])));
  }
}

/**
 * Helper form element validator: integer > 0.
 */
function _element_validate_integer_positive($element, &$form_state) {
  $value = $element['#value'];
  if ($value !== '' && (!is_numeric($value) || intval($value) != $value || $value <= 0)) {
    form_error($element, t('%name must be a positive integer.', array('%name' => $element['#title'])));
  }
}

/**
 * Helper form element validator: number.
 */
function _element_validate_number($element, &$form_state) {
  $value = $element['#value'];
  if ($value != '' && !is_numeric($value)) {
    form_error($element, t('%name must be a number.', array('%name' => $element['#title'])));
  }
}
