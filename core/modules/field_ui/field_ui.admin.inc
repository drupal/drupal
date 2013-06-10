<?php

/**
 * @file
 * Administrative interface for custom field type creation.
 */

/**
 * Page callback: Lists all defined fields for quick reference.
 *
 * @see field_ui_menu()
 */
function field_ui_fields_list() {
  $instances = field_info_instances();
  $field_types = field_info_field_types();
  $bundles = entity_get_bundles();
  $entity_manager = Drupal::entityManager();

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
        $admin_path = $entity_manager->getAdminPath($entity_type, $bundle);
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
