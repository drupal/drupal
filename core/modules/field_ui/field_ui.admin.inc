<?php

/**
 * @file
 * Administrative interface for custom field type creation.
 */

use Drupal\field\Plugin\Core\Entity\FieldInstance;
use Drupal\field_ui\FieldOverview;
use Drupal\field_ui\DisplayOverview;

/**
 * Page callback: Lists all defined fields for quick reference.
 *
 * @see field_ui_menu()
 */
function field_ui_fields_list() {
  $instances = field_info_instances();
  $field_types = field_info_field_types();
  $bundles = entity_get_bundles();

  $modules = system_rebuild_module_data();

  $header = array(
    t('Field name'),
    array('data' => t('Field type'), 'class' => array(RESPONSIVE_PRIORITY_MEDIUM)),
    t('Used in'),
  );
  $rows = array();
  foreach ($instances as $entity_type => $type_bundles) {
    foreach ($type_bundles as $bundle => $bundle_instances) {
      foreach ($bundle_instances as $field_name => $instance) {
        $field = field_info_field($field_name);

        // Initialize the row if we encounter the field for the first time.
        if (!isset($rows[$field_name])) {
          $rows[$field_name]['class'] = $field['locked'] ? array('menu-disabled') : array('');
          $rows[$field_name]['data'][0] = $field['locked'] ? t('@field_name (Locked)', array('@field_name' => $field_name)) : $field_name;
          $module_name = $field_types[$field['type']]['module'];
          $rows[$field_name]['data'][1] = $field_types[$field['type']]['label'] . ' ' . t('(module: !module)', array('!module' => $modules[$module_name]->info['name']));
        }

        // Add the current instance.
        $admin_path = field_ui_bundle_admin_path($entity_type, $bundle);
        $rows[$field_name]['data'][2][] = $admin_path ? l($bundles[$entity_type][$bundle]['label'], $admin_path . '/fields') : $bundles[$entity_type][$bundle]['label'];
      }
    }
  }
  foreach ($rows as $field_name => $cell) {
    $rows[$field_name]['data'][2] = implode(', ', $cell['data'][2]);
  }
  if (empty($rows)) {
    $output = t('No fields have been defined yet.');
  }
  else {
    // Sort rows by field name.
    ksort($rows);
    $output = theme('table', array('header' => $header, 'rows' => $rows));
  }
  return $output;
}

/**
 * Displays a message listing the inactive fields of a given bundle.
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
      '%widget_type' => isset($widget_types[$instance['widget']['type']]) ? $widget_types[$instance['widget']['type']]['label'] : $instance['widget']['type'],
      '%widget_module' => $instance['widget']['module'],
      ));
    }
    drupal_set_message(t('Inactive fields are not shown unless their providing modules are enabled. The following fields are not enabled: !list', array('!list' => theme('item_list', array('items' => $list)))), 'error');
  }
}

/**
 * Determines the rendering order of an array representing a tree.
 *
 * Callback for array_reduce() within field_ui_table_pre_render().
 */
function _field_ui_reduce_order($array, $a) {
  $array = !isset($array) ? array() : $array;
  if ($a['name']) {
    $array[] = $a['name'];
  }
  if (!empty($a['children'])) {
    uasort($a['children'], 'drupal_sort_weight');
    $array = array_merge($array, array_reduce($a['children'], '_field_ui_reduce_order'));
  }
  return $array;
}

/**
 * Returns the region to which a row in the 'Manage fields' screen belongs.
 *
 * This function is used as a #region_callback in
 * Drupal\field_ui\DisplayOverview::form(). It is called during
 * field_ui_table_pre_render().
 */
function field_ui_field_overview_row_region($row) {
  switch ($row['#row_type']) {
    case 'field':
    case 'extra_field':
      return 'content';
    case 'add_new_field':
      // If no input in 'label', assume the row has not been dragged out of the
      // 'add new' section.
      return (!empty($row['label']['#value']) ? 'content' : 'hidden');
  }
}

/**
 * Returns the region to which a row in the 'Manage display' screen belongs.
 *
 * This function is used as a #region_callback in
 * Drupal\field_ui\FieldOverview::form(), and is called during
 * field_ui_table_pre_render().
 */
function field_ui_display_overview_row_region($row) {
  switch ($row['#row_type']) {
    case 'field':
    case 'extra_field':
      return ($row['format']['type']['#value'] == 'hidden' ? 'hidden' : 'content');
  }
}

/**
 * Render API callback: Performs pre-render tasks on field_ui_table elements.
 *
 * This function is assigned as a #pre_render callback in
 * field_ui_element_info().
 *
 * @see drupal_render().
 */
function field_ui_table_pre_render($elements) {
  $js_settings = array();

  // For each region, build the tree structure from the weight and parenting
  // data contained in the flat form structure, to determine row order and
  // indentation.
  $regions = $elements['#regions'];
  $tree = array('' => array('name' => '', 'children' => array()));
  $trees = array_fill_keys(array_keys($regions), $tree);

  $parents = array();
  $list = drupal_map_assoc(element_children($elements));

  // Iterate on rows until we can build a known tree path for all of them.
  while ($list) {
    foreach ($list as $name) {
      $row = &$elements[$name];
      $parent = $row['parent_wrapper']['parent']['#value'];
      // Proceed if parent is known.
      if (empty($parent) || isset($parents[$parent])) {
        // Grab parent, and remove the row from the next iteration.
        $parents[$name] = $parent ? array_merge($parents[$parent], array($parent)) : array();
        unset($list[$name]);

        // Determine the region for the row.
        $function = $row['#region_callback'];
        $region_name = $function($row);

        // Add the element in the tree.
        $target = &$trees[$region_name][''];
        foreach ($parents[$name] as $key) {
          $target = &$target['children'][$key];
        }
        $target['children'][$name] = array('name' => $name, 'weight' => $row['weight']['#value']);

        // Add tabledrag indentation to the first row cell.
        if ($depth = count($parents[$name])) {
          $children = element_children($row);
          $cell = current($children);
          $row[$cell]['#prefix'] = theme('indentation', array('size' => $depth)) . (isset($row[$cell]['#prefix']) ? $row[$cell]['#prefix'] : '');
        }

        // Add row id and associate JS settings.
        $id = drupal_html_class($name);
        $row['#attributes']['id'] = $id;
        if (isset($row['#js_settings'])) {
          $row['#js_settings'] += array(
            'rowHandler' => $row['#row_type'],
            'name' => $name,
            'region' => $region_name,
          );
          $js_settings[$id] = $row['#js_settings'];
        }
      }
    }
  }
  // Determine rendering order from the tree structure.
  foreach ($regions as $region_name => $region) {
    $elements['#regions'][$region_name]['rows_order'] = array_reduce($trees[$region_name], '_field_ui_reduce_order');
  }

  $elements['#attached']['js'][] = array(
    'type' => 'setting',
    'data' => array('fieldUIRowsData' => $js_settings),
  );

  return $elements;
}

/**
 * Returns HTML for Field UI overview tables.
 *
 * @param $variables
 *   An associative array containing:
 *   - elements: An associative array containing a Form API structure to be
 *     rendered as a table.
 *
 * @ingroup themeable
 */
function theme_field_ui_table($variables) {
  $elements = $variables['elements'];
  $table = array();
  $js_settings = array();

  // Add table headers and attributes.
  foreach (array('header', 'attributes') as $key) {
    if (isset($elements["#$key"])) {
      $table[$key] = $elements["#$key"];
    }
  }

  // Determine the colspan to use for region rows, by checking the number of
  // columns in the headers.
  $columns_count = 0;
  foreach ($table['header'] as $header) {
    $columns_count += (is_array($header) && isset($header['colspan']) ? $header['colspan'] : 1);
  }

  // Render rows, region by region.
  foreach ($elements['#regions'] as $region_name => $region) {
    $region_name_class = drupal_html_class($region_name);

    // Add region rows.
    if (isset($region['title']) && empty($region['invisible'])) {
      $table['rows'][] = array(
        'class' => array('region-title', 'region-' . $region_name_class . '-title'),
        'no_striping' => TRUE,
        'data' => array(
          array('data' => $region['title'], 'colspan' => $columns_count),
        ),
      );
    }
    if (isset($region['message'])) {
      $class = (empty($region['rows_order']) ? 'region-empty' : 'region-populated');
      $table['rows'][] = array(
        'class' => array('region-message', 'region-' . $region_name_class . '-message', $class),
        'no_striping' => TRUE,
        'data' => array(
          array('data' => $region['message'], 'colspan' => $columns_count),
        ),
      );
    }

    // Add form rows, in the order determined at pre-render time.
    foreach ($region['rows_order'] as $name) {
      $element = $elements[$name];

      $row = array('data' => array());
      if (isset($element['#attributes'])) {
        $row += $element['#attributes'];
      }

      // Render children as table cells.
      foreach (element_children($element) as $cell_key) {
        $child = &$element[$cell_key];
        // Do not render a cell for children of #type 'value'.
        if (!(isset($child['#type']) && $child['#type'] == 'value')) {
          $cell = array('data' => drupal_render($child));
          if (isset($child['#cell_attributes'])) {
            $cell += $child['#cell_attributes'];
          }
          $row['data'][] = $cell;
        }
      }
      $table['rows'][] = $row;
    }
  }

  return theme('table', $table);
}

/**
 * Returns the built and processed 'Manage fields' form of a bundle.
 *
 * The resulting form allows fields and pseudo-fields to be re-ordered.
 *
 * @param string $entity_type
 *   The entity type for the fieldable entity.
 * @param string $bundle
 *   The bundle for the fieldable entity.
 *
 * @return
 *   The processed form for the given entity type and bundle.
 *
 * @see field_ui_menu()
 * @see Drupal\field_ui\FieldOverview::validate()
 * @see Drupal\field_ui\FieldOverview::submit()
 * @ingroup forms
 */
function field_ui_field_overview($entity_type, $bundle) {
  $bundle = field_extract_bundle($entity_type, $bundle);
  field_ui_inactive_message($entity_type, $bundle);

  return drupal_get_form(new FieldOverview($entity_type, $bundle));
}

/**
 * Render API callback: Checks if a field machine name is taken.
 *
 * @param $value
 *   The machine name, not prefixed with 'field_'.
 *
 * @return
 *   Whether or not the field machine name is taken.
 */
function _field_ui_field_name_exists($value) {
  // Prefix with 'field_'.
  $field_name = 'field_' . $value;

  // We need to check inactive fields as well, so we can't use
  // field_info_fields().
  return (bool) field_read_fields(array('field_name' => $field_name), array('include_inactive' => TRUE));
}

/**
 * Returns the built and processed 'Manage display' form of a bundle.
 *
 * The resulting form allows fields and pseudo-fields to be re-ordered.
 *
 * @param string $entity_type
 *   The entity type for the fieldable entity.
 * @param string $bundle
 *   The bundle for the fieldable entity.
 * @param string $view_mode
 *   The view mode for the fieldable entity.
 *
 * @return
 *   The processed form for the given entity type and bundle.
 *
 * @see field_ui_menu()
 * @see field_ui_display_overview_multistep_submit()
 * @see Drupal\field_ui\DisplayOverview::submit()
 * @ingroup forms
 */
function field_ui_display_overview($entity_type, $bundle, $view_mode) {
  $bundle = field_extract_bundle($entity_type, $bundle);
  field_ui_inactive_message($entity_type, $bundle);

  return drupal_get_form(new DisplayOverview($entity_type, $bundle, $view_mode));
}

/**
 * Returns an array of field_type options.
 */
function field_ui_field_type_options() {
  $options = &drupal_static(__FUNCTION__);

  if (!isset($options)) {
    $options = array();
    $field_types = field_info_field_types();
    $field_type_options = array();
    foreach ($field_types as $name => $field_type) {
      // Skip field types which have no widget types, or should not be add via
      // uesr interface.
      if (field_ui_widget_type_options($name) && empty($field_type['no_ui'])) {
        $options[$name] = $field_type['label'];
      }
    }
    asort($options);
  }
  return $options;
}

/**
 * Returns an array of widget type options for a field type.
 *
 * If no field type is provided, returns a nested array of all widget types,
 * keyed by field type human name.
 */
function field_ui_widget_type_options($field_type = NULL, $by_label = FALSE) {
  $options = &drupal_static(__FUNCTION__);

  if (!isset($options)) {
    $options = array();
    $field_types = field_info_field_types();
    $widget_types = field_info_widget_types();
    uasort($widget_types, 'drupal_sort_weight');
    foreach ($widget_types as $name => $widget_type) {
      foreach ($widget_type['field_types'] as $widget_field_type) {
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
 * Returns an array of formatter options for a field type.
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
      foreach ($formatter['field_types'] as $formatter_field_type) {
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
 * Returns an array of existing fields to be added to a bundle.
 */
function field_ui_existing_field_options($entity_type, $bundle) {
  $info = array();
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
          // - fields that cannot be added to the entity type,
          // - fields that should not be added via user interface.

          if (empty($field['locked'])
            && !field_info_instance($entity_type, $field['field_name'], $bundle)
            && (empty($field['entity_types']) || in_array($entity_type, $field['entity_types']))
            && empty($field_types[$field['type']]['no_ui'])) {
            $info[$instance['field_name']] = array(
              'type' => $field['type'],
              'type_label' => $field_types[$field['type']]['label'],
              'field' => $field['field_name'],
              'label' => $instance['label'],
              'widget_type' => $instance['widget']['type'],
            );
          }
        }
      }
    }
  }
  return $info;
}

/**
 * Form constructor for the field settings edit page.
 *
 * @see field_ui_menu()
 * @see field_ui_field_settings_form_submit()
 * @ingroup forms
 */
function field_ui_field_settings_form($form, &$form_state, $instance) {
  $bundle = $instance['bundle'];
  $entity_type = $instance['entity_type'];
  $field = field_info_field($instance['field_name']);
  $form['#field'] = $field;
  $form['#entity_type'] = $entity_type;
  $form['#bundle'] = $bundle;

  drupal_set_title($instance['label']);

  $description = '<p>' . t('These settings apply to the %field field everywhere it is used. These settings impact the way that data is stored in the database and cannot be changed once data has been created.', array('%field' => $instance['label'])) . '</p>';

  // Create a form structure for the field values.
  $form['field'] = array(
    '#prefix' => $description,
    '#tree' => TRUE,
  );

  // See if data already exists for this field.
  // If so, prevent changes to the field settings.
  $has_data = field_has_data($field);
  if ($has_data) {
    $form['field']['#prefix'] = '<div class="messages error">' . t('There is data for this field in the database. The field settings can no longer be changed.') . '</div>' . $form['field']['#prefix'];
  }

  // Build the configurable field values.
  $cardinality = $field['cardinality'];
  $form['field']['container'] = array(
    // We can't use the container element because it doesn't support the title
    // or description properties.
    '#type' => 'item',
    '#field_prefix' => '<div class="container-inline">',
    '#field_suffix' => '</div>',
    '#title' => t('Maximum number of values users can enter'),
  );
  $form['field']['container']['cardinality'] = array(
    '#type' => 'select',
    '#options' => drupal_map_assoc(range(1, 5)) + array(FIELD_CARDINALITY_UNLIMITED => t('Unlimited')) + array('other' => t('More')),
    '#default_value' => ($cardinality < 6) ? $cardinality : 'other',
  );
  // @todo Convert when http://drupal.org/node/1207060 gets in.
  $form['field']['container']['cardinality_other'] = array(
    '#type' => 'number',
    '#default_value' => $cardinality > 5 ? $cardinality : 6,
    '#min' => 1,
    '#title' => t('Custom value'),
    '#title_display' => 'invisible',
    '#states' => array(
      'visible' => array(
       ':input[name="field[container][cardinality]"]' => array('value' => 'other'),
      ),
    ),
  );
  if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_DEFAULT) {
    $form['field']['container']['#description'] = t('%unlimited will provide an %add-more button so users can add as many values as they like.', array(
      '%unlimited' => t('Unlimited'),
      '%add-more' => t('Add another item'),
    ));
  }

  // Build the non-configurable field values.
  $form['field']['field_name'] = array('#type' => 'value', '#value' => $field['field_name']);
  $form['field']['type'] = array('#type' => 'value', '#value' => $field['type']);
  $form['field']['module'] = array('#type' => 'value', '#value' => $field['module']);
  $form['field']['active'] = array('#type' => 'value', '#value' => $field['active']);

  // Add settings provided by the field module. The field module is
  // responsible for not returning settings that cannot be changed if
  // the field already has data.
  $form['field']['settings'] = array(
    '#weight' => 10,
  );
  $additions = module_invoke($field['module'], 'field_settings_form', $field, $instance, $has_data);
  if (is_array($additions)) {
    $form['field']['settings'] += $additions;
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save field settings'));
  return $form;
}

/**
 * Form validation handler for field_ui_field_edit_form().
 *
 * @see field_ui_field_settings_form_submit().
 */
function field_ui_field_settings_form_validate($form, &$form_state) {
  // Validate field cardinality.
  $cardinality = $form_state['values']['field']['container']['cardinality'];
  $cardinality_other = $form_state['values']['field']['container']['cardinality_other'];
  if ($cardinality == 'other' && empty($cardinality_other)) {
    form_error($form['field']['container']['cardinality_other'], t('Number of values is required.'));
  }
}

/**
 * Form submission handler for field_ui_field_settings_form().
 */
function field_ui_field_settings_form_submit($form, &$form_state) {
  $form_values = $form_state['values'];
  $field_values = $form_values['field'];

  // Save field cardinality.
  $cardinality = $field_values['container']['cardinality'];
  $cardinality_other = $field_values['container']['cardinality_other'];
  $cardinality_other = $form_state['values']['field']['container']['cardinality_other'];
  if ($cardinality == 'other') {
    $cardinality = $cardinality_other;
  }
  $field_values['cardinality'] = $cardinality;
  unset($field_values['container']);

  // Merge incoming form values into the existing field.
  $field = field_info_field($field_values['field_name']);
  foreach ($field_values as $key => $value) {
    $field[$key] = $value;
  }

  $entity_type = $form['#entity_type'];
  $bundle = $form['#bundle'];
  $instance = field_info_instance($entity_type, $field['field_name'], $bundle);

  // Update the field.
  try {
    field_update_field($field);
    drupal_set_message(t('Updated field %label field settings.', array('%label' => $instance['label'])));
    $form_state['redirect'] = field_ui_next_destination($entity_type, $bundle);
  }
  catch (Exception $e) {
    drupal_set_message(t('Attempt to update field %label failed: %message.', array('%label' => $instance['label'], '%message' => $e->getMessage())), 'error');
  }
}

/**
 * Form constructor for the widget selection form.
 *
 * @see field_ui_menu()
 * @see field_ui_widget_type_form_submit()
 * @ingroup forms
 */
function field_ui_widget_type_form($form, &$form_state, FieldInstance $instance) {
  drupal_set_title($instance['label']);

  $bundle = $instance['bundle'];
  $entity_type = $instance['entity_type'];
  $field_name = $instance['field_name'];

  $field = field_info_field($field_name);
  $bundles = entity_get_bundles();
  $bundle_label = $bundles[$entity_type][$bundle]['label'];

  $form = array(
    '#bundle' => $bundle,
    '#entity_type' => $entity_type,
    '#field_name' => $field_name,
  );

  $form['widget_type'] = array(
    '#type' => 'select',
    '#title' => t('Widget type'),
    '#required' => TRUE,
    '#options' => field_ui_widget_type_options($field['type']),
    '#default_value' => $instance->getWidget()->getPluginId(),
    '#description' => t('The type of form element you would like to present to the user when creating this field in the %type type.', array('%type' => $bundle_label)),
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Continue'));

  $form['#validate'] = array();
  $form['#submit'] = array('field_ui_widget_type_form_submit');

  return $form;
}

/**
 * Form submission handler for field_ui_widget_type_form().
 */
function field_ui_widget_type_form_submit($form, &$form_state) {
  $form_values = $form_state['values'];
  $bundle = $form['#bundle'];
  $entity_type = $form['#entity_type'];
  $field_name = $form['#field_name'];

  // Retrieve the stored instance settings to merge with the incoming values.
  $instance = field_read_instance($entity_type, $field_name, $bundle);

  // Set the right module information.
  $widget_type = field_info_widget_types($form_values['widget_type']);
  $widget_module = $widget_type['module'];

  $instance['widget']['type'] = $form_values['widget_type'];
  $instance['widget']['module'] = $widget_module;

  try {
    field_update_instance($instance);
    drupal_set_message(t('Changed the widget for field %label.', array('%label' => $instance['label'])));

    if ($instance['required'] && empty($instance['default_value']) && empty($instance['default_value_function']) && $instance['widget']['type'] == 'field_hidden') {
      drupal_set_message(t('Field %label is required and uses the "hidden" widget. You might want to configure a default value.', array('%label' => $instance['label'])), 'warning');
    }
  }
  catch (Exception $e) {
    drupal_set_message(t('There was a problem changing the widget for field %label.', array('%label' => $instance['label'])), 'error');
  }

  $form_state['redirect'] = field_ui_next_destination($entity_type, $bundle);
}

/**
 * Form constructor for removing a field instance from a bundle.
 *
 * @see field_ui_menu()
 * @see field_ui_field_delete_form_submit()
 * @ingroup forms
 */
function field_ui_field_delete_form($form, &$form_state, $instance) {
  $bundle = $instance['bundle'];
  $entity_type = $instance['entity_type'];
  $field = field_info_field($instance['field_name']);

  $admin_path = field_ui_bundle_admin_path($entity_type, $bundle);

  $form['entity_type'] = array('#type' => 'value', '#value' => $entity_type);
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
 * Form submission handler for field_ui_field_delete_form().
 *
 * Removes a field instance from a bundle. If the field has no more instances,
 * it will be marked as deleted too.
 */
function field_ui_field_delete_form_submit($form, &$form_state) {
  $form_values = $form_state['values'];
  $field_name = $form_values['field_name'];
  $bundle = $form_values['bundle'];
  $entity_type = $form_values['entity_type'];

  $field = field_info_field($field_name);
  $instance = field_info_instance($entity_type, $field_name, $bundle);
  $bundles = entity_get_bundles();
  $bundle_label = $bundles[$entity_type][$bundle]['label'];

  if (!empty($bundle) && $field && !$field['locked'] && $form_values['confirm']) {
    field_delete_instance($instance);
    drupal_set_message(t('The field %field has been deleted from the %type content type.', array('%field' => $instance['label'], '%type' => $bundle_label)));
  }
  else {
    drupal_set_message(t('There was a problem removing the %field from the %type content type.', array('%field' => $instance['label'], '%type' => $bundle_label)), 'error');
  }

  $admin_path = field_ui_bundle_admin_path($entity_type, $bundle);
  $form_state['redirect'] = field_ui_get_destinations(array($admin_path . '/fields'));

  // Fields are purged on cron. However field module prevents disabling modules
  // when field types they provided are used in a field until it is fully
  // purged. In the case that a field has minimal or no content, a single call
  // to field_purge_batch() will remove it from the system. Call this with a
  // low batch limit to avoid administrators having to wait for cron runs when
  // removing instances that meet this criteria.
  field_purge_batch(10);
}

/**
 * Form constructor for the field instance settings form.
 *
 * @see field_ui_menu()
 * @see field_ui_field_edit_form_validate()
 * @see field_ui_field_edit_form_submit()
 * @see field_ui_field_edit_form_delete_submit()
 * @ingroup forms
 */
function field_ui_field_edit_form($form, &$form_state, $instance) {
  $bundle = $instance['bundle'];
  $entity_type = $instance['entity_type'];
  $field = field_info_field($instance['field_name']);
  $bundles = entity_get_bundles();

  drupal_set_title(t('%instance settings for %bundle', array(
    '%instance' => $instance['label'],
    '%bundle' => $bundles[$entity_type][$bundle]['label'],
  )), PASS_THROUGH);

  $form['#field'] = $field;
  $form['#instance'] = $instance;
  // Create an arbitrary entity object (used by the 'default value' widget).
  $ids = (object) array('entity_type' => $instance['entity_type'], 'bundle' => $instance['bundle'], 'entity_id' => NULL);
  $form['#entity'] = _field_create_entity_from_ids($ids);
  $form['#entity']->field_ui_default_value = TRUE;

  if (!empty($field['locked'])) {
    $form['locked'] = array(
      '#markup' => t('The field %field is locked and cannot be edited.', array('%field' => $instance['label'])),
    );
    return $form;
  }

  $widget_type = field_info_widget_types($instance['widget']['type']);

  // Create a form structure for the instance values.
  $form['instance'] = array(
    '#tree' => TRUE,
  );

  // Build the non-configurable instance values.
  $form['instance']['field_name'] = array(
    '#type' => 'value',
    '#value' => $instance['field_name'],
  );
  $form['instance']['entity_type'] = array(
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

  $form['instance']['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Help text'),
    '#default_value' => !empty($instance['description']) ? $instance['description'] : '',
    '#rows' => 5,
    '#description' => t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', array('@tags' => _field_filter_xss_display_allowed_tags())) . '<br />' . t('This field supports tokens.'),
    '#weight' => -10,
  );

  $form['instance']['required'] = array(
    '#type' => 'checkbox',
    '#title' => t('Required field'),
    '#default_value' => !empty($instance['required']),
    '#weight' => -5,
  );

  // Build the widget component of the instance.
  $form['instance']['widget']['type'] = array(
    '#type' => 'value',
    '#value' => $instance['widget']['type'],
  );

  // Add additional field instance settings from the field module.
  $additions = module_invoke($field['module'], 'field_instance_settings_form', $field, $instance, $form_state);
  if (is_array($additions)) {
    $form['instance']['settings'] = $additions;
    $form['instance']['settings']['#weight'] = 10;
  }

  // Add widget settings for the widget type.
  $additions = $instance->getWidget()->settingsForm($form, $form_state);
  $form['instance']['widget']['settings'] = $additions ? $additions : array('#type' => 'value', '#value' => array());
  $form['instance']['widget']['#weight'] = 20;

  // Add handling for default value if not provided by any other module.
  if (field_behaviors_widget('default_value', $instance) == FIELD_BEHAVIOR_DEFAULT && empty($instance['default_value_function'])) {
    $form['instance']['default_value_widget'] = field_ui_default_value_widget($field, $instance, $form, $form_state);
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save settings')
  );
  $form['actions']['delete'] = array(
    '#type' => 'submit',
    '#value' => t('Delete field'),
    '#submit' => array('field_ui_field_edit_form_delete_submit'),
  );
  return $form;
}

/**
 * Form submission handler for 'Delete' button in field_ui_field_edit_form().
 */
function field_ui_field_edit_form_delete_submit($form, &$form_state) {
  $destination = array();
  if (isset($_GET['destination'])) {
    $destination = drupal_get_destination();
    unset($_GET['destination']);
  }
  $instance = $form['#instance'];
  $form_state['redirect'] = array('admin/structure/types/manage/' . $instance['bundle'] . '/fields/' . $instance['field_name'] . '/delete', array('query' => $destination));
}

/**
 * Builds the default value widget for a given field instance.
 */
function field_ui_default_value_widget($field, $instance, &$form, &$form_state) {
  $field_name = $field['field_name'];
  $entity = $form['#entity'];

  $element = array(
    '#type' => 'details',
    '#title' => t('Default value'),
    '#tree' => TRUE,
    '#description' => t('The default value for this field, used when creating new content.'),
    // Stick to an empty 'parents' on this form in order not to breaks widgets
    // that do not use field_widget_[field|instance]() and still access
    // $form_state['field'] directly.
    '#parents' => array(),
  );

  // Adjust the instance definition used for the form element. We want a
  // non-required input and no description.
  $instance['required'] = FALSE;
  $instance['description'] = '';

  // Adjust the instance definition to use the default widget of this field type
  // instead of the hidden widget.
  if ($instance['widget']['type'] == 'field_hidden') {
    $field_type = field_info_field_types($field['type']);
    $default_widget = field_info_widget_types($field_type['default_widget']);

    $instance['widget'] = array(
      'type' => $default_widget['id'],
      'settings' => $default_widget['settings'],
      'weight' => 0,
    );
  }

  // Insert the widget. Since we do not use the "official" instance definition,
  // the whole flow cannot use field_invoke_method().
  $items = array();
  if (!empty($instance['default_value'])) {
    $items = (array) $instance['default_value'];
  }
  $element += $instance->getWidget()->form($entity, LANGUAGE_NOT_SPECIFIED, $items, $element, $form_state);

  return $element;
}

/**
 * Form validation handler for field_ui_field_edit_form().
 *
 * @see field_ui_field_edit_form_submit().
 */
function field_ui_field_edit_form_validate($form, &$form_state) {
  // Take the incoming values as the $instance definition, so that the 'default
  // value' gets validated using the instance settings being submitted.
  $instance = $form['#instance'];
  $field_name = $instance['field_name'];
  $entity = $form['#entity'];

  if (isset($form['instance']['default_value_widget'])) {
    $element = $form['instance']['default_value_widget'];

    // Extract the 'default value'.
    $items = array();
    $instance->getWidget()->extractFormValues($entity, LANGUAGE_NOT_SPECIFIED, $items, $element, $form_state);

    // Grab the field definition from $form_state.
    $field_state = field_form_get_state($element['#parents'], $field_name, LANGUAGE_NOT_SPECIFIED, $form_state);
    $field = $field_state['field'];

    // Validate the value.
    $errors = array();
    $function = $field['module'] . '_field_validate';
    if (function_exists($function)) {
      $function(NULL, $field, $instance, LANGUAGE_NOT_SPECIFIED, $items, $errors);
    }

    // Report errors.
    if (isset($errors[$field_name][LANGUAGE_NOT_SPECIFIED])) {
      // Store reported errors in $form_state.
      $field_state['errors'] = $errors[$field_name][LANGUAGE_NOT_SPECIFIED];
      field_form_set_state($element['#parents'], $field_name, LANGUAGE_NOT_SPECIFIED, $form_state, $field_state);

      // Assign reported errors to the correct form element.
      $instance->getWidget()->flagErrors($entity, LANGUAGE_NOT_SPECIFIED, $items, $element, $form_state);
    }
  }
}

/**
 * Form submission handler for field_ui_field_edit_form().
 *
 * @see field_ui_field_edit_form_validate().
 */
function field_ui_field_edit_form_submit($form, &$form_state) {
  $instance = $form['#instance'];
  $field = $form['#field'];
  $entity = $form['#entity'];

  // Handle the default value.
  if (isset($form['instance']['default_value_widget'])) {
    $element = $form['instance']['default_value_widget'];

    // Extract field values.
    $items = array();
    $instance->getWidget()->extractFormValues($entity, LANGUAGE_NOT_SPECIFIED, $items, $element, $form_state);

    $instance['default_value'] = $items ? $items : NULL;
  }

  // Merge incoming values into the instance.
  foreach ($form_state['values']['instance'] as $key => $value) {
    $instance[$key] = $value;
  }
  field_update_instance($instance);

  drupal_set_message(t('Saved %label configuration.', array('%label' => $instance['label'])));

  if ($instance['required'] && empty($instance['default_value']) && empty($instance['default_value_function']) && $instance['widget']['type'] == 'field_hidden') {
    drupal_set_message(t('Field %label is required and uses the "hidden" widget. You might want to configure a default value.', array('%label' => $instance['label'])), 'warning');
  }

  $form_state['redirect'] = field_ui_next_destination($instance['entity_type'], $instance['bundle']);
}

/**
 * Extracts next redirect path from an array of multiple destinations.
 *
 * @see field_ui_next_destination()
 */
function field_ui_get_destinations($destinations) {
  $path = array_shift($destinations);
  $options = drupal_parse_url($path);
  if ($destinations) {
    $options['query']['destinations'] = $destinations;
  }
  return array($options['path'], $options);
}

/**
 * Returns the next redirect path in a multipage sequence.
 */
function field_ui_next_destination($entity_type, $bundle) {
  $destinations = !empty($_REQUEST['destinations']) ? $_REQUEST['destinations'] : array();
  if (!empty($destinations)) {
    unset($_REQUEST['destinations']);
    return field_ui_get_destinations($destinations);
  }
  $admin_path = field_ui_bundle_admin_path($entity_type, $bundle);
  return $admin_path . '/fields';
}
