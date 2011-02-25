<?php

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
        $rows[$field_name]['data'][1] = $field_types[$field['type']]['label'];
        $rows[$field_name]['data'][2][] = $admin_path ? l($bundles[$entity_type][$bundle]['label'], $admin_path . '/fields') : $bundles[$entity_type][$bundle]['label'];
        $rows[$field_name]['class'] = $field['locked'] ? array('menu-disabled') : array('');
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
      '%widget_type' => isset($widget_types[$instance['widget']['type']]) ? $widget_types[$instance['widget']['type']]['label'] : $instance['widget']['type'],
      '%widget_module' => $instance['widget']['module'],
      ));
    }
    drupal_set_message(t('Inactive fields are not shown unless their providing modules are enabled. The following fields are not enabled: !list', array('!list' => theme('item_list', array('items' => $list)))), 'error');
  }
}

/**
 * Helper function: determines the rendering order of a tree array.
 *
 * This is intended as a callback for array_reduce().
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
 * This function is used as a #row_callback in field_ui_field_overview_form(),
 * and is called during field_ui_table_pre_render().
 */
function field_ui_field_overview_row_region($row) {
  switch ($row['#row_type']) {
    case 'field':
    case 'extra_field':
      return 'main';
    case 'add_new_field':
      // If no input in 'label', assume the row has not been dragged out of the
      // 'add new' section.
      return (!empty($row['label']['#value']) ? 'main' : 'add_new');
  }
}

/**
 * Returns the region to which a row in the 'Manage display' screen belongs.
 *
 * This function is used as a #row_callback in field_ui_field_overview_form(),
 * and is called during field_ui_table_pre_render().
 */
function field_ui_display_overview_row_region($row) {
  switch ($row['#row_type']) {
    case 'field':
    case 'extra_field':
      return ($row['format']['type']['#value'] == 'hidden' ? 'hidden' : 'visible');
  }
}

/**
 * Pre-render callback for field_ui_table elements.
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
          $cell = current(element_children($row));
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
  $colums_count = 0;
  foreach ($table['header'] as $header) {
    $colums_count += (is_array($header) && isset($header['colspan']) ? $header['colspan'] : 1);
  }

  // Render rows, region by region.
  foreach ($elements['#regions'] as $region_name => $region) {
    $region_name_class = drupal_html_class($region_name);

    // Add region rows.
    if (isset($region['title'])) {
      $table['rows'][] = array(
        'class' => array('region-title', 'region-' . $region_name_class . '-title'),
        'no_striping' => TRUE,
        'data' => array(
          array('data' => $region['title'], 'colspan' => $colums_count),
        ),
      );
    }
    if (isset($region['message'])) {
      $class = (empty($region['rows_order']) ? 'region-empty' : 'region-populated');
      $table['rows'][] = array(
        'class' => array('region-message', 'region-' . $region_name_class . '-message', $class),
        'no_striping' => TRUE,
        'data' => array(
          array('data' => $region['message'], 'colspan' => $colums_count),
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

      foreach (element_children($element) as $cell_key) {
        $cell = array('data' => drupal_render($element[$cell_key]));
        if (isset($element[$cell_key]['#cell_attributes'])) {
          $cell += $element[$cell_key]['#cell_attributes'];
        }
        $row['data'][] = $cell;
      }
      $table['rows'][] = $row;
    }
  }

  return theme('table', $table);
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

  $extra_fields = field_info_extra_fields($entity_type, $bundle, 'form');

  $form += array(
    '#entity_type' => $entity_type,
    '#bundle' => $bundle,
    '#fields' => array_keys($instances),
    '#extra' => array_keys($extra_fields),
  );

  $table = array(
    '#type' => 'field_ui_table',
    '#tree' => TRUE,
    '#header' => array(
      t('Label'),
      t('Weight'),
      t('Parent'),
      t('Name'),
      t('Field'),
      t('Widget'),
      array('data' => t('Operations'), 'colspan' => 2),
    ),
    '#parent_options' => array(),
    '#regions' => array(
      'main' => array('message' => t('No fields are present yet.')),
      'add_new' => array('title' => '&nbsp;'),
    ),
    '#attributes' => array(
      'class' => array('field-ui-overview'),
      'id' => 'field-overview',
    ),
  );

  // Fields.
  foreach ($instances as $name => $instance) {
    $field = field_info_field($instance['field_name']);
    $admin_field_path = $admin_path . '/fields/' . $instance['field_name'];
    $table[$name] = array(
      '#attributes' => array('class' => array('draggable', 'tabledrag-leaf')),
      '#row_type' => 'field',
      '#region_callback' => 'field_ui_field_overview_row_region',
      'label' => array(
        '#markup' => check_plain($instance['label']),
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#title' => t('Weight for @title', array('@title' => $instance['label'])),
        '#title_display' => 'invisible',
        '#default_value' => $instance['widget']['weight'],
        '#size' => 3,
        '#attributes' => array('class' => array('field-weight')),
       ),
      'parent_wrapper' => array(
        'parent' => array(
          '#type' => 'select',
          '#title' => t('Parent for @title', array('@title' => $instance['label'])),
          '#title_display' => 'invisible',
          '#options' => $table['#parent_options'],
          '#empty_value' => '',
          '#attributes' => array('class' => array('field-parent')),
          '#parents' => array('fields', $name, 'parent'),
        ),
        'hidden_name' => array(
          '#type' => 'hidden',
          '#default_value' => $name,
          '#attributes' => array('class' => array('field-name')),
        ),
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
    );

    if (!empty($instance['locked'])) {
      $table[$name]['edit'] = array('#value' => t('Locked'));
      $table[$name]['delete'] = array();
      $table[$name]['#attributes']['class'][] = 'menu-disabled';
    }
  }

  // Non-field elements.
  foreach ($extra_fields as $name => $extra_field) {
    $table[$name] = array(
      '#attributes' => array('class' => array('draggable', 'tabledrag-leaf')),
      '#row_type' => 'extra_field',
      '#region_callback' => 'field_ui_field_overview_row_region',
      'label' => array(
        '#markup' => check_plain($extra_field['label']),
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#default_value' => $extra_field['weight'],
        '#size' => 3,
        '#attributes' => array('class' => array('field-weight')),
        '#title_display' => 'invisible',
        '#title' => t('Weight for @title', array('@title' => $extra_field['label'])),
      ),
      'parent_wrapper' => array(
        'parent' => array(
          '#type' => 'select',
          '#title' => t('Parent for @title', array('@title' => $extra_field['label'])),
          '#title_display' => 'invisible',
          '#options' => $table['#parent_options'],
          '#empty_value' => '',
          '#attributes' => array('class' => array('field-parent')),
          '#parents' => array('fields', $name, 'parent'),
        ),
        'hidden_name' => array(
          '#type' => 'hidden',
          '#default_value' => $name,
          '#attributes' => array('class' => array('field-name')),
        ),
      ),
      'field_name' => array(
        '#markup' => $name,
      ),
      'type' => array(
        '#markup' => isset($extra_field['description']) ? $extra_field['description'] : '',
        '#cell_attributes' => array('colspan' => 2),
      ),
      'edit' => array(
        '#markup' => isset($extra_field['edit']) ? $extra_field['edit'] : '',
      ),
      'delete' => array(
        '#markup' => isset($extra_field['delete']) ? $extra_field['delete'] : '',
      ),
    );
  }

  // Additional row: add new field.
  $max_weight = field_info_max_weight($entity_type, $bundle, 'form');
  $field_type_options = field_ui_field_type_options();
  $widget_type_options = field_ui_widget_type_options(NULL, TRUE);
  if ($field_type_options && $widget_type_options) {
    $name = '_add_new_field';
    $table[$name] = array(
      '#attributes' => array('class' => array('draggable', 'tabledrag-leaf', 'add-new')),
      '#row_type' => 'add_new_field',
      '#region_callback' => 'field_ui_field_overview_row_region',
      'label' => array(
        '#type' => 'textfield',
        '#title' => t('New field label'),
        '#title_display' => 'invisible',
        '#size' => 15,
        '#description' => t('Label'),
        '#prefix' => '<div class="label-input"><div class="add-new-placeholder">' . t('Add new field') .'</div>',
        '#suffix' => '</div>',
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#default_value' => $max_weight + 1,
        '#size' => 3,
        '#title_display' => 'invisible',
        '#title' => t('Weight for new field'),
        '#attributes' => array('class' => array('field-weight')),
        '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
      ),
      'parent_wrapper' => array(
        'parent' => array(
          '#type' => 'select',
          '#title' => t('Parent for new field'),
          '#title_display' => 'invisible',
          '#options' => $table['#parent_options'],
          '#empty_value' => '',
          '#attributes' => array('class' => array('field-parent')),
          '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
          '#parents' => array('fields', $name, 'parent'),
        ),
        'hidden_name' => array(
          '#type' => 'hidden',
          '#default_value' => $name,
          '#attributes' => array('class' => array('field-name')),
        ),
      ),
      'field_name' => array(
        '#type' => 'textfield',
        '#title' => t('New field name'),
        '#title_display' => 'invisible',
        // This field should stay LTR even for RTL languages.
        '#field_prefix' => '<span dir="ltr">field_',
        '#field_suffix' => '</span>&lrm;',
        '#attributes' => array('dir'=>'ltr'),
        '#size' => 10,
        '#description' => t('Field name (a-z, 0-9, _)'),
        '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
      ),
      'type' => array(
        '#type' => 'select',
        '#title' => t('Type of new field'),
        '#title_display' => 'invisible',
        '#options' => $field_type_options,
        '#empty_option' => t('- Select a field type -'),
        '#description' => t('Type of data to store.'),
        '#attributes' => array('class' => array('field-type-select')),
        '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
      ),
      'widget_type' => array(
        '#type' => 'select',
        '#title' => t('Widget for new field'),
        '#title_display' => 'invisible',
        '#options' => $widget_type_options,
        '#empty_option' => t('- Select a widget -'),
        '#description' => t('Form element to edit the data.'),
        '#attributes' => array('class' => array('widget-type-select')),
        '#cell_attributes' => array('colspan' => 3),
        '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
      ),
    );
  }

  // Additional row: add existing field.
  $existing_field_options = field_ui_existing_field_options($entity_type, $bundle);
  if ($existing_field_options && $widget_type_options) {
    $name = '_add_existing_field';
    $table[$name] = array(
      '#attributes' => array('class' => array('draggable', 'tabledrag-leaf', 'add-new')),
      '#row_type' => 'add_new_field',
      '#region_callback' => 'field_ui_field_overview_row_region',
      'label' => array(
        '#type' => 'textfield',
        '#title' => t('Existing field label'),
        '#title_display' => 'invisible',
        '#size' => 15,
        '#description' => t('Label'),
        '#attributes' => array('class' => array('label-textfield')),
        '#prefix' => '<div class="label-input"><div class="add-new-placeholder">' . t('Add existing field') .'</div>',
        '#suffix' => '</div>',
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#default_value' => $max_weight + 2,
        '#size' => 3,
        '#title_display' => 'invisible',
        '#title' => t('Weight for added field'),
        '#attributes' => array('class' => array('field-weight')),
        '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
      ),
      'parent_wrapper' => array(
        'parent' => array(
          '#type' => 'select',
          '#title' => t('Parent for existing field'),
          '#title_display' => 'invisible',
          '#options' => $table['#parent_options'],
          '#empty_value' => '',
          '#attributes' => array('class' => array('field-parent')),
          '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
          '#parents' => array('fields', $name, 'parent'),
        ),
        'hidden_name' => array(
          '#type' => 'hidden',
          '#default_value' => $name,
          '#attributes' => array('class' => array('field-name')),
        ),
      ),
      'field_name' => array(
        '#type' => 'select',
        '#title' => t('Existing field to share'),
        '#title_display' => 'invisible',
        '#options' => $existing_field_options,
        '#empty_option' => t('- Select an existing field -'),
        '#description' => t('Field to share'),
        '#attributes' => array('class' => array('field-select')),
        '#cell_attributes' => array('colspan' => 2),
        '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
      ),
      'widget_type' => array(
        '#type' => 'select',
        '#title' => t('Widget for existing field'),
        '#title_display' => 'invisible',
        '#options' => $widget_type_options,
        '#empty_option' => t('- Select a widget -'),
        '#description' => t('Form element to edit the data.'),
        '#attributes' => array('class' => array('widget-type-select')),
        '#cell_attributes' => array('colspan' => 3),
        '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
      ),
    );
  }
  $form['fields'] = $table;

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save'));

  $form['#attached']['css'][] = drupal_get_path('module', 'field_ui') . '/field_ui.css';
  $form['#attached']['js'][] = drupal_get_path('module', 'field_ui') . '/field_ui.js';

  // Add settings for the update selects behavior.
  $js_fields = array();
  foreach ($existing_field_options as $field_name => $fields) {
    $field = field_info_field($field_name);
    $instance = field_info_instance($form['#entity_type'], $field_name, $form['#bundle']);
    $js_fields[$field_name] = array('label' => $instance['label'], 'type' => $field['type'], 'widget' => $instance['widget']['type']);
  }

  $form['#attached']['js'][] = array(
    'type' => 'setting',
    'data' => array('fields' => $js_fields, 'fieldWidgetTypes' => field_ui_widget_type_options()),
  );

  // Add tabledrag behavior.
  $form['#attached']['drupal_add_tabledrag'][] = array('field-overview', 'order', 'sibling', 'field-weight');
  $form['#attached']['drupal_add_tabledrag'][] = array('field-overview', 'match', 'parent', 'field-parent', 'field-parent', 'field-name');

  return $form;
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
  $field = $form_state['values']['fields']['_add_new_field'];

  // Validate if any information was provided in the 'add new field' row.
  if (array_filter(array($field['label'], $field['field_name'], $field['type'], $field['widget_type']))) {
    // Missing label.
    if (!$field['label']) {
      form_set_error('fields][_add_new_field][label', t('Add new field: you need to provide a label.'));
    }

    // Missing field name.
    if (!$field['field_name']) {
      form_set_error('fields][_add_new_field][field_name', t('Add new field: you need to provide a field name.'));
    }
    // Field name validation.
    else {
      $field_name = $field['field_name'];

      // Add the 'field_' prefix.
      if (substr($field_name, 0, 6) != 'field_') {
        $field_name = 'field_' . $field_name;
        form_set_value($form['fields']['_add_new_field']['field_name'], $field_name, $form_state);
      }

      // Invalid field name.
      if (!preg_match('!^field_[a-z0-9_]+$!', $field_name)) {
        form_set_error('fields][_add_new_field][field_name', t('Add new field: the field name %field_name is invalid. The name must include only lowercase unaccentuated letters, numbers, and underscores.', array('%field_name' => $field_name)));
      }
      if (strlen($field_name) > 32) {
        form_set_error('fields][_add_new_field][field_name', t("Add new field: the field name %field_name is too long. The name is limited to 32 characters, including the 'field_' prefix.", array('%field_name' => $field_name)));
      }

      // Field name already exists. We need to check inactive fields as well, so
      // we can't use field_info_fields().
      $fields = field_read_fields(array('field_name' => $field_name), array('include_inactive' => TRUE));
      if ($fields) {
        form_set_error('fields][_add_new_field][field_name', t('Add new field: the field name %field_name already exists.', array('%field_name' => $field_name)));
      }
    }

    // Missing field type.
    if (!$field['type']) {
      form_set_error('fields][_add_new_field][type', t('Add new field: you need to select a field type.'));
    }

    // Missing widget type.
    if (!$field['widget_type']) {
      form_set_error('fields][_add_new_field][widget_type', t('Add new field: you need to select a widget.'));
    }
    // Wrong widget type.
    elseif ($field['type']) {
      $widget_types = field_ui_widget_type_options($field['type']);
      if (!isset($widget_types[$field['widget_type']])) {
        form_set_error('fields][_add_new_field][widget_type', t('Add new field: invalid widget.'));
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
  // this bundle.
  if (isset($form_state['values']['fields']['_add_existing_field'])) {
    $field = $form_state['values']['fields']['_add_existing_field'];

    // Validate if any information was provided in the 'add existing field' row.
    if (array_filter(array($field['label'], $field['field_name'], $field['widget_type']))) {
      // Missing label.
      if (!$field['label']) {
        form_set_error('fields][_add_existing_field][label', t('Add existing field: you need to provide a label.'));
      }

      // Missing existing field name.
      if (!$field['field_name']) {
        form_set_error('fields][_add_existing_field][field_name', t('Add existing field: you need to select a field.'));
      }

      // Missing widget type.
      if (!$field['widget_type']) {
        form_set_error('fields][_add_existing_field][widget_type', t('Add existing field: you need to select a widget.'));
      }
      // Wrong widget type.
      elseif ($field['field_name'] && ($existing_field = field_info_field($field['field_name']))) {
        $widget_types = field_ui_widget_type_options($existing_field['type']);
        if (!isset($widget_types[$field['widget_type']])) {
          form_set_error('fields][_add_existing_field][widget_type', t('Add existing field: invalid widget.'));
        }
      }
    }
  }
}

/**
 * Submit handler for the field overview form.
 */
function field_ui_field_overview_form_submit($form, &$form_state) {
  $form_values = $form_state['values']['fields'];
  $entity_type = $form['#entity_type'];
  $bundle = $form['#bundle'];
  $admin_path = _field_ui_bundle_admin_path($entity_type, $bundle);

  $bundle_settings = field_bundle_settings($entity_type, $bundle);

  // Update field weights.
  foreach ($form_values as $key => $values) {
    if (in_array($key, $form['#fields'])) {
      $instance = field_read_instance($entity_type, $key, $bundle);
      $instance['widget']['weight'] = $values['weight'];
      field_update_instance($instance);
    }
    elseif (in_array($key, $form['#extra'])) {
      $bundle_settings['extra_fields']['form'][$key]['weight'] = $values['weight'];
    }
  }

  field_bundle_settings($entity_type, $bundle, $bundle_settings);

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
      'entity_type' => $entity_type,
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
      $destinations[] = $admin_path . '/fields/' . $field['field_name'];

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
        'entity_type' => $entity_type,
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
  else {
    drupal_set_message(t('Your settings have been saved.'));
  }
}

/**
 * Menu callback; presents field display settings for a given view mode.
 */
function field_ui_display_overview_form($form, &$form_state, $entity_type, $bundle, $view_mode) {
  $bundle = field_extract_bundle($entity_type, $bundle);

  field_ui_inactive_message($entity_type, $bundle);
  $admin_path = _field_ui_bundle_admin_path($entity_type, $bundle);

  // Gather type information.
  $instances = field_info_instances($entity_type, $bundle);
  $field_types = field_info_field_types();
  $extra_fields = field_info_extra_fields($entity_type, $bundle, 'display');

  $form_state += array(
    'formatter_settings_edit' => NULL,
  );

  $form += array(
    '#entity_type' => $entity_type,
    '#bundle' => $bundle,
    '#view_mode' => $view_mode,
    '#fields' => array_keys($instances),
    '#extra' => array_keys($extra_fields),
  );

  if (empty($instances) && empty($extra_fields)) {
    drupal_set_message(t('There are no fields yet added. You can add new fields on the <a href="@link">Manage fields</a> page.', array('@link' => url($admin_path . '/fields'))), 'warning');
    return $form;
  }

  $table = array(
    '#type' => 'field_ui_table',
    '#tree' => TRUE,
    '#header' => array(
      t('Field'),
      t('Weight'),
      t('Parent'),
      t('Label'),
      array('data' => t('Format'), 'colspan' => 3),
    ),
    '#regions' => array(
      'visible' => array('message' => t('No field is displayed.')),
      'hidden' => array('title' => t('Hidden'), 'message' => t('No field is hidden.')),
    ),
    '#parent_options' => array(),
    '#attributes' => array(
      'class' => array('field-ui-overview'),
      'id' => 'field-display-overview',
    ),
    // Add Ajax wrapper.
    '#prefix' => '<div id="field-display-overview-wrapper">',
    '#suffix' => '</div>',
  );

  $field_label_options = array(
    'above' => t('Above'),
    'inline' => t('Inline'),
    'hidden' => t('<Hidden>'),
  );
  $extra_visibility_options = array(
    'visible' => t('Visible'),
    'hidden' => t('Hidden'),
  );

  // Field rows.
  foreach ($instances as $name => $instance) {
    $field = field_info_field($instance['field_name']);
    $display = $instance['display'][$view_mode];
    $table[$name] = array(
      '#attributes' => array('class' => array('draggable', 'tabledrag-leaf')),
      '#row_type' => 'field',
      '#region_callback' => 'field_ui_display_overview_row_region',
      '#js_settings' => array(
        'rowHandler' => 'field',
        'defaultFormatter' => $field_types[$field['type']]['default_formatter'],
      ),
      'human_name' => array(
        '#markup' => check_plain($instance['label']),
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#title' => t('Weight for @title', array('@title' => $instance['label'])),
        '#title_display' => 'invisible',
        '#default_value' => $display['weight'],
        '#size' => 3,
        '#attributes' => array('class' => array('field-weight')),
      ),
      'parent_wrapper' => array(
        'parent' => array(
          '#type' => 'select',
          '#title' => t('Label display for @title', array('@title' => $instance['label'])),
          '#title_display' => 'invisible',
          '#options' => $table['#parent_options'],
          '#empty_value' => '',
          '#attributes' => array('class' => array('field-parent')),
          '#parents' => array('fields', $name, 'parent'),
        ),
        'hidden_name' => array(
          '#type' => 'hidden',
          '#default_value' => $name,
          '#attributes' => array('class' => array('field-name')),
        ),
      ),
      'label' => array(
        '#type' => 'select',
        '#title' => t('Label display for @title', array('@title' => $instance['label'])),
        '#title_display' => 'invisible',
        '#options' => $field_label_options,
        '#default_value' => $display['label'],
      ),
    );

    $formatter_options = field_ui_formatter_options($field['type']);
    $formatter_options['hidden'] = t('<Hidden>');
    $table[$name]['format'] = array(
      'type' => array(
        '#type' => 'select',
        '#title' => t('Formatter for @title', array('@title' => $instance['label'])),
        '#title_display' => 'invisible',
        '#options' => $formatter_options,
        '#default_value' => $display['type'],
        '#parents' => array('fields', $name, 'type'),
        '#attributes' => array('class' => array('field-formatter-type')),
      ),
      'settings_edit_form' => array(),
    );

    // Formatter settings.

    // Check the currently selected formatter, and merge persisted values for
    // formatter settings.
    if (isset($form_state['values']['fields'][$name]['type'])) {
      $formatter_type = $form_state['values']['fields'][$name]['type'];
    }
    else {
      $formatter_type = $display['type'];
    }
    if (isset($form_state['formatter_settings'][$name])) {
      $settings = $form_state['formatter_settings'][$name];
    }
    else {
      $settings = $display['settings'];
    }
    $settings += field_info_formatter_settings($formatter_type);

    $instance['display'][$view_mode]['type'] = $formatter_type;
    $formatter = field_info_formatter_types($formatter_type);
    $instance['display'][$view_mode]['module'] = $formatter['module'];
    $instance['display'][$view_mode]['settings'] = $settings;

    // Base button element for the various formatter settings actions.
    $base_button = array(
      '#submit' => array('field_ui_display_overview_multistep_submit'),
      '#ajax' => array(
        'callback' => 'field_ui_display_overview_multistep_js',
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ),
      '#field_name' => $name,
    );

    if ($form_state['formatter_settings_edit'] == $name) {
      // We are currently editing this field's formatter settings. Display the
      // settings form and submit buttons.
      $table[$name]['format']['settings_edit_form'] = array();

      $settings_form = array();
      $function = $formatter['module'] . '_field_formatter_settings_form';
      if (function_exists($function)) {
        $settings_form = $function($field, $instance, $view_mode, $form, $form_state);
      }

      if ($settings_form) {
        $table[$name]['format']['#cell_attributes'] = array('colspan' => 3);
        $table[$name]['format']['settings_edit_form'] = array(
          '#type' => 'container',
          '#attributes' => array('class' => array('field-formatter-settings-edit-form')),
          '#parents' => array('fields', $name, 'settings_edit_form'),
          'label' => array(
            '#markup' => t('Format settings:') . ' <span class="formatter-name">' . $formatter['label'] . '</span>',
          ),
          'settings' => $settings_form,
          'actions' => array(
            '#type' => 'actions',
            'save_settings' => $base_button + array(
              '#type' => 'submit',
              '#name' => $name . '_formatter_settings_update',
              '#value' => t('Update'),
              '#op' => 'update',
            ),
            'cancel_settings' => $base_button + array(
              '#type' => 'submit',
              '#name' => $name . '_formatter_settings_cancel',
              '#value' => t('Cancel'),
              '#op' => 'cancel',
              // Do not check errors for the 'Cancel' button, but make sure we
              // get the value of the 'formatter type' select.
              '#limit_validation_errors' => array(array('fields', $name, 'type')),
            ),
          ),
        );
        $table[$name]['#attributes']['class'][] = 'field-formatter-settings-editing';
      }
    }
    else {
      // Display a summary of the current formatter settings.
      $summary = module_invoke($formatter['module'], 'field_formatter_settings_summary', $field, $instance, $view_mode);
      $table[$name]['settings_summary'] = array();
      $table[$name]['settings_edit'] = array();
      if ($summary) {
        $table[$name]['settings_summary'] = array(
          '#markup' => '<div class="field-formatter-summary">' . $summary . '</div>',
          '#cell_attributes' => array('class' => array('field-formatter-summary-cell')),
        );
        $table[$name]['settings_edit'] = $base_button + array(
          '#type' => 'image_button',
          '#name' => $name . '_formatter_settings_edit',
          '#src' => 'misc/configure.png',
          '#attributes' => array('class' => array('field-formatter-settings-edit'), 'alt' => t('Edit')),
          '#op' => 'edit',
          // Do not check errors for the 'Edit' button, but make sure we get
          // the value of the 'formatter type' select.
          '#limit_validation_errors' => array(array('fields', $name, 'type')),
          '#prefix' => '<div class="field-formatter-settings-edit-wrapper">',
          '#suffix' => '</div>',
        );
      }
    }
  }

  // Non-field elements.
  foreach ($extra_fields as $name => $extra_field) {
    $display = $extra_field['display'][$view_mode];
    $table[$name] = array(
      '#attributes' => array('class' => array('draggable', 'tabledrag-leaf')),
      '#row_type' => 'extra_field',
      '#region_callback' => 'field_ui_display_overview_row_region',
      '#js_settings' => array('rowHandler' => 'field'),
      'human_name' => array(
        '#markup' => check_plain($extra_field['label']),
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#title' => t('Weight for @title', array('@title' => $extra_field['label'])),
        '#title_display' => 'invisible',
        '#default_value' => $display['weight'],
        '#size' => 3,
        '#attributes' => array('class' => array('field-weight')),
      ),
      'parent_wrapper' => array(
        'parent' => array(
          '#type' => 'select',
          '#title' => t('Parents for @title', array('@title' => $extra_field['label'])),
          '#title_display' => 'invisible',
          '#options' => $table['#parent_options'],
          '#empty_value' => '',
          '#attributes' => array('class' => array('field-parent')),
          '#parents' => array('fields', $name, 'parent'),
        ),
        'hidden_name' => array(
          '#type' => 'hidden',
          '#default_value' => $name,
          '#attributes' => array('class' => array('field-name')),
        ),
      ),
      'empty_cell' => array(
        '#markup' => '&nbsp;',
      ),
      'format' => array(
        'type' => array(
          '#type' => 'select',
          '#title' => t('Visibility for @title', array('@title' => $extra_field['label'])),
          '#title_display' => 'invisible',
          '#options' => $extra_visibility_options,
          '#default_value' => $display['visible'] ? 'visible' : 'hidden',
          '#parents' => array('fields', $name, 'type'),
          '#attributes' => array('class' => array('field-formatter-type')),
        ),
      ),
      'settings_summary' => array(),
      'settings_edit' => array(),
    );
  }

  $form['fields'] = $table;

  // Custom display settings.
  if ($view_mode == 'default') {
    $form['modes'] = array(
      '#type' => 'fieldset',
      '#title' => t('Custom display settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    // Collect options and default values for the 'Custom display settings'
    // checkboxes.
    $options = array();
    $default = array();
    $entity_info = entity_get_info($entity_type);
    $view_modes = $entity_info['view modes'];
    $view_mode_settings = field_view_mode_settings($entity_type, $bundle);
    foreach ($view_modes as $view_mode_name => $view_mode_info) {
      $options[$view_mode_name] = $view_mode_info['label'];
      if (!empty($view_mode_settings[$view_mode_name]['custom_settings'])) {
        $default[] = $view_mode_name;
      }
    }
    $form['modes']['view_modes_custom'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Use custom display settings for the following view modes'),
      '#options' => $options,
      '#default_value' => $default,
    );
  }

  // In overviews involving nested rows from contributed modules (i.e
  // field_group), the 'format type' selects can trigger a series of changes in
  // child rows. The #ajax behavior is therefore not attached directly to the
  // selects, but triggered by the client-side script through a hidden #ajax
  // 'Refresh' button. A hidden 'refresh_rows' input tracks the name of
  // affected rows.
  $form['refresh_rows'] = array('#type' => 'hidden');
  $form['refresh'] = array(
    '#type' => 'submit',
    '#value' => t('Refresh'),
    '#op' => 'refresh_table',
    '#submit' => array('field_ui_display_overview_multistep_submit'),
    '#ajax' => array(
      'callback' => 'field_ui_display_overview_multistep_js',
      'wrapper' => 'field-display-overview-wrapper',
      'effect' => 'fade',
      // The button stays hidden, so we hide the Ajax spinner too. Ad-hoc
      // spinners will be added manually by the client-side script.
      'progress' => 'none',
    ),
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save'));

  $form['#attached']['js'][] = drupal_get_path('module', 'field_ui') . '/field_ui.js';
  $form['#attached']['css'][] = drupal_get_path('module', 'field_ui') . '/field_ui.css';

  // Add tabledrag behavior.
  $form['#attached']['drupal_add_tabledrag'][] = array('field-display-overview', 'order', 'sibling', 'field-weight');
  $form['#attached']['drupal_add_tabledrag'][] = array('field-display-overview', 'match', 'parent', 'field-parent', 'field-parent', 'field-name');

  return $form;
}


/**
 * Form submit handler for multistep buttons on the 'Manage display' screen.
 */
function field_ui_display_overview_multistep_submit($form, &$form_state) {
  $trigger = $form_state['triggering_element'];
  $op = $trigger['#op'];

  switch ($op) {
    case 'edit':
      // Store the field whose settings are currently being edited.
      $field_name = $trigger['#field_name'];
      $form_state['formatter_settings_edit'] = $field_name;
      break;

    case 'update':
      // Store the saved settings, and set the field back to 'non edit' mode.
      $field_name = $trigger['#field_name'];
      $values = $form_state['values']['fields'][$field_name]['settings_edit_form']['settings'];
      $form_state['formatter_settings'][$field_name] = $values;
      unset($form_state['formatter_settings_edit']);
      break;

    case 'cancel':
      // Set the field back to 'non edit' mode.
      unset($form_state['formatter_settings_edit']);
      break;

    case 'refresh_table':
      // If the currently edited field is one of the rows to be refreshed, set
      // it back to 'non edit' mode.
      $updated_rows = explode(' ', $form_state['values']['refresh_rows']);
      if (isset($form_state['formatter_settings_edit']) && in_array($form_state['formatter_settings_edit'], $updated_rows)) {
        unset($form_state['formatter_settings_edit']);
      }
      break;
  }

  $form_state['rebuild'] = TRUE;
}

/**
 * Ajax handler for multistep buttons on the 'Manage display' screen.
 */
function field_ui_display_overview_multistep_js($form, &$form_state) {
  $trigger = $form_state['triggering_element'];
  $op = $trigger['#op'];

  // Pick the elements that need ro receive the ajax-new-content effect.
  switch ($op) {
    case 'edit':
      $updated_rows = array($trigger['#field_name']);
      $updated_columns = array('format');
      break;

    case 'update':
    case 'cancel':
      $updated_rows = array($trigger['#field_name']);
      $updated_columns = array('format', 'settings_summary', 'settings_edit');
      break;

    case 'refresh_table':
      $updated_rows = array_values(explode(' ', $form_state['values']['refresh_rows']));
      $updated_columns = array('settings_summary', 'settings_edit');
      break;
  }

  foreach ($updated_rows as $name) {
    foreach ($updated_columns as $key) {
      $element = &$form['fields'][$name][$key];
      $element['#prefix'] = '<div class="ajax-new-content">' . (isset($element['#prefix']) ? $element['#prefix'] : '');
      $element['#suffix'] = (isset($element['#suffix']) ? $element['#suffix'] : '') . '</div>';
    }
  }

  // Return the whole table.
  return $form['fields'];
}

/**
 * Submit handler for the display overview form.
 */
function field_ui_display_overview_form_submit($form, &$form_state) {
  $form_values = $form_state['values'];
  $entity_type = $form['#entity_type'];
  $bundle = $form['#bundle'];
  $view_mode = $form['#view_mode'];

  // Save data for 'regular' fields.
  foreach ($form['#fields'] as $field_name) {
    // Retrieve the stored instance settings to merge with the incoming values.
    $instance = field_read_instance($entity_type, $field_name, $bundle);
    $values = $form_values['fields'][$field_name];
    // Get formatter settings. They lie either directly in submitted form
    // values (if the whole form was submitted while some formatter
    // settings were being edited), or have been persisted in
    // $form_state.
    $settings = array();
    if (isset($values['settings_edit_form']['settings'])) {
      $settings = $values['settings_edit_form']['settings'];
    }
    elseif (isset($form_state['formatter_settings'][$field_name])) {
      $settings = $form_state['formatter_settings'][$field_name];
    }
    elseif (isset($instance['display'][$view_mode]['settings'])) {
      $settings = $instance['display'][$view_mode]['settings'];
    }

    // Only save settings actually used by the selected formatter.
    $default_settings = field_info_formatter_settings($values['type']);
    $settings = array_intersect_key($settings, $default_settings);

    $instance['display'][$view_mode] = array(
      'label' => $values['label'],
      'type' => $values['type'],
      'weight' => $values['weight'],
      'settings' => $settings,
    );
    field_update_instance($instance);
  }

  // Get current bundle settings.
  $bundle_settings = field_bundle_settings($entity_type, $bundle);

  // Save data for 'extra' fields.
  foreach ($form['#extra'] as $name) {
    $bundle_settings['extra_fields']['display'][$name][$view_mode] = array(
      'weight' => $form_values['fields'][$name]['weight'],
      'visible' => $form_values['fields'][$name]['type'] == 'visible',
    );
  }

  // Save view modes data.
  if ($view_mode == 'default') {
    $entity_info = entity_get_info($entity_type);
    foreach ($form_values['view_modes_custom'] as $view_mode_name => $value) {
      // Display a message for each view mode newly configured to use custom
      // settings.
      $view_mode_settings = field_view_mode_settings($entity_type, $bundle);
      if (!empty($value) && empty($view_mode_settings[$view_mode_name]['custom_settings'])) {
        $view_mode_label = $entity_info['view modes'][$view_mode_name]['label'];
        $path = _field_ui_bundle_admin_path($entity_type, $bundle) . "/display/$view_mode_name";
        drupal_set_message(t('The %view_mode mode now uses custom display settings. You might want to <a href="@url">configure them</a>.', array('%view_mode' => $view_mode_label, '@url' => url($path))));
        // Initialize the newly customized view mode with the display settings
        // from the default view mode.
        _field_ui_add_default_view_mode_settings($entity_type, $bundle, $view_mode_name, $bundle_settings);
      }
      $bundle_settings['view_modes'][$view_mode_name]['custom_settings'] = !empty($value);
    }
  }

  // Save updated bundle settings.
  field_bundle_settings($entity_type, $bundle, $bundle_settings);

  drupal_set_message(t('Your settings have been saved.'));
}

/**
 * Helper function for field_ui_display_overview_form_submit().
 *
 * When an administrator decides to use custom display settings for a view mode,
 * that view mode needs to be initialized with the display settings for the
 * 'default' view mode, which it was previously using. This helper function
 * adds the new custom display settings to this bundle's instances, and saves
 * them. It also modifies the passed-in $settings array, which the caller can
 * then save using field_bundle_settings().
 *
 * @see field_bundle_settings()
 *
 * @param $entity_type
 *   The bundle's entity type.
 * @param $bundle
 *   The bundle whose view mode is being customized.
 * @param $view_mode
 *   The view mode that the administrator has set to use custom settings.
 * @param $settings
 *   An associative array of bundle settings, as expected by
 *   field_bundle_settings().
 */
function _field_ui_add_default_view_mode_settings($entity_type, $bundle, $view_mode, &$settings) {
  // Update display settings for field instances.
  $instances = field_read_instances(array('entity_type' => $entity_type, 'bundle' => $bundle));
  foreach ($instances as $instance) {
    // If this field instance has display settings defined for this view mode,
    // respect those settings.
    if (!isset($instance['display'][$view_mode])) {
      // The instance doesn't specify anything for this view mode, so use the
      // default display settings.
      $instance['display'][$view_mode] = $instance['display']['default'];
      field_update_instance($instance);
    }
  }

  // Update display settings for 'extra fields'.
  foreach (array_keys($settings['extra_fields']['display']) as $name) {
    if (!isset($settings['extra_fields']['display'][$name][$view_mode])) {
      $settings['extra_fields']['display'][$name][$view_mode] = $settings['extra_fields']['display'][$name]['default'];
    }
  }
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
          // - fields that cannot be added to the entity type,
          // - fields that that shoud not be added via user interface.

          if (empty($field['locked'])
            && !field_info_instance($entity_type, $field['field_name'], $bundle)
            && (empty($field['entity_types']) || in_array($entity_type, $field['entity_types']))
            && empty($field_types[$field['type']]['no_ui'])) {
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
function field_ui_field_settings_form($form, &$form_state, $instance) {
  $bundle = $instance['bundle'];
  $entity_type = $instance['entity_type'];
  $field = field_info_field($instance['field_name']);

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
    $form['field']['#description'] = '<div class="messages error">' . t('There is data for this field in the database. The field settings can no longer be changed.') . '</div>' . $form['field']['#description'];
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
  $form['#entity_type'] = $entity_type;
  $form['#bundle'] = $bundle;

  $form['actions'] = array('#type' => 'actions');
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

  $entity_type = $form['#entity_type'];
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
function field_ui_widget_type_form($form, &$form_state, $instance) {
  drupal_set_title($instance['label']);

  $bundle = $instance['bundle'];
  $entity_type = $instance['entity_type'];
  $field_name = $instance['field_name'];

  $field = field_info_field($field_name);
  $field_type = field_info_field_types($field['type']);
  $widget_type = field_info_widget_types($instance['widget']['type']);
  $bundles = field_info_bundles();
  $bundle_label = $bundles[$entity_type][$bundle]['label'];

  $form = array(
    '#bundle' => $bundle,
    '#entity_type' => $entity_type,
    '#field_name' => $field_name,
  );

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

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Continue'));

  $form['#validate'] = array();
  $form['#submit'] = array('field_ui_widget_type_form_submit');

  return $form;
}

/**
 * Submit the change in widget type.
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
  }
  catch (FieldException $e) {
    drupal_set_message(t('There was a problem changing the widget for field %label.', array('%label' => $instance['label'])));
  }

  $form_state['redirect'] = field_ui_next_destination($entity_type, $bundle);
}

/**
 * Menu callback; present a form for removing a field instance from a bundle.
 */
function field_ui_field_delete_form($form, &$form_state, $instance) {
  $bundle = $instance['bundle'];
  $entity_type = $instance['entity_type'];
  $field = field_info_field($instance['field_name']);

  $admin_path = _field_ui_bundle_admin_path($entity_type, $bundle);

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
 * Removes a field instance from a bundle.
 *
 * If the field has no more instances, it will be marked as deleted too.
 */
function field_ui_field_delete_form_submit($form, &$form_state) {
  $form_values = $form_state['values'];
  $field_name = $form_values['field_name'];
  $bundle = $form_values['bundle'];
  $entity_type = $form_values['entity_type'];

  $field = field_info_field($field_name);
  $instance = field_info_instance($entity_type, $field_name, $bundle);
  $bundles = field_info_bundles();
  $bundle_label = $bundles[$entity_type][$bundle]['label'];

  if (!empty($bundle) && $field && !$field['locked'] && $form_values['confirm']) {
    field_delete_instance($instance);
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
function field_ui_field_edit_form($form, &$form_state, $instance) {
  $bundle = $instance['bundle'];
  $entity_type = $instance['entity_type'];
  $field = field_info_field($instance['field_name']);

  drupal_set_title($instance['label']);

  $form['#field'] = $field;
  $form['#instance'] = $instance;

  if (!empty($field['locked'])) {
    $form['locked'] = array(
      '#markup' => t('The field %field is locked and cannot be edited.', array('%field' => $instance['label'])),
    );
    return $form;
  }

  $field_type = field_info_field_types($field['type']);
  $widget_type = field_info_widget_types($instance['widget']['type']);
  $bundles = field_info_bundles();

  // Create a form structure for the instance values.
  $form['instance'] = array(
    '#tree' => TRUE,
    '#type' => 'fieldset',
    '#title' => t('%type settings', array('%type' => $bundles[$entity_type][$bundle]['label'])),
    '#description' => t('These settings apply only to the %field field when used in the %type type.', array(
      '%field' => $instance['label'],
      '%type' => $bundles[$entity_type][$bundle]['label'],
    )),
    // Ensure field_ui_field_edit_instance_pre_render() gets called in addition
    // to, not instead of, the #pre_render function(s) needed by all fieldsets.
    '#pre_render' => array_merge(array('field_ui_field_edit_instance_pre_render'), element_info_property('fieldset', '#pre_render', array())),
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
    '#weight' => -5,
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

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save settings'));
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
    // Stick to an empty 'parents' on this form in order not to breaks widgets
    // that do not use field_widget_[field|instance]() and still access
    // $form_state['field'] directly.
    '#parents' => array(),
  );

  // Insert the widget.
  $items = $instance['default_value'];
  $instance['required'] = FALSE;
  $instance['description'] = '';

  // @todo Allow multiple values (requires more work on 'add more' JS handler).
  $element += field_default_form(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $element, $form_state, 0);

  return $element;
}

/**
 * Form validation handler for field instance settings form.
 */
function field_ui_field_edit_form_validate($form, &$form_state) {
  // Take the incoming values as the $instance definition, so that the 'default
  // value' gets validated using the instance settings being submitted.
  $instance = $form_state['values']['instance'];
  $field_name = $instance['field_name'];

  if (isset($form['instance']['default_value_widget'])) {
    $element = $form['instance']['default_value_widget'];

    $field_state = field_form_get_state($element['#parents'], $field_name, LANGUAGE_NONE, $form_state);
    $field = $field_state['field'];

    // Extract the 'default value'.
    $items = array();
    field_default_extract_form_values(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $element, $form_state);

    // Validate the value and report errors.
    $errors = array();
    $function = $field['module'] . '_field_validate';
    if (function_exists($function)) {
      $function(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $errors);
    }
    if (isset($errors[$field_name][LANGUAGE_NONE])) {
      // Store reported errors in $form_state.
      $field_state['errors'] = $errors[$field_name][LANGUAGE_NONE];
      field_form_set_state($element['#parents'], $field_name, LANGUAGE_NONE, $form_state, $field_state);
      // Assign reported errors to the correct form element.
      field_default_form_errors(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $element, $form_state);
    }
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
  if (isset($form['instance']['default_value_widget'])) {
    $element = $form['instance']['default_value_widget'];

    // Extract field values.
    $items = array();
    field_default_extract_form_values(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $element, $form_state);
    field_default_submit(NULL, NULL, $field, $instance, LANGUAGE_NONE, $items, $element, $form_state);

    $instance['default_value'] = $items ? $items : NULL;
  }

  // Retrieve the stored instance settings to merge with the incoming values.
  $instance_source = field_read_instance($instance['entity_type'], $instance['field_name'], $instance['bundle']);
  $instance = array_merge($instance_source, $instance);
  field_update_instance($instance);

  drupal_set_message(t('Saved %label configuration.', array('%label' => $instance['label'])));

  $form_state['redirect'] = field_ui_next_destination($instance['entity_type'], $instance['bundle']);
}

/**
 * Helper functions to handle multipage redirects.
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
