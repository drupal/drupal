<?php

/**
 * @file
 * Administration functions for layouts.
 */

/**
 * Page callback: Presents a list of layouts.
 *
 * @return array
 *   An array as expected by drupal_render().
 *
 * @see layout_menu()
 */
function layout_page_list() {
  // Get list of layouts defined by enabled modules and themes.
  $layouts = layout_manager()->getDefinitions();

  $rows = array();
  $header = array(t('Name'), t('Source'));
  foreach ($layouts as $name => $layout) {
    $provider_info = system_get_info($layout['provider']['type'], $layout['provider']['provider']);

    // Build table columns for this row.
    $row = array();
    $row['name'] = l($layout['title'], 'admin/structure/templates/manage/' . $name);
    // Type can either be 'module' or 'theme'.
    $row['provider'] = t('@name @type', array('@name' => $provider_info['name'], '@type' => t($layout['provider']['type'])));

    $rows[] = $row;
  }

  $build = array();
  $build['table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );
  return $build;

  // Ensure the provider types are translatable. These do not need to run,
  // just inform the static code parser of these source strings.
  t('module');
  t('theme');
}

/**
 * Page callback: Demonstrates a layout template.
 *
 * @param string $key
 *   The key of the page layout being requested.
 *
 * @return array
 *   An array as expected by drupal_render().
 *
 * @see layout_menu()
 */
function layout_page_view($key) {
  $layout = layout_manager()->getDefinition($key);
  drupal_set_title(t('View template %name', array('%name' => $layout['title'])), PASS_THROUGH);

  // Render the layout in an admin context with region demonstrations.
  $instance = layout_manager()->createInstance($key, array());
  $regions = $instance->getRegions();
  foreach ($regions as $region => $info) {
    $regions[$region] = '<div class="layout-region-demonstration">' . check_plain($info['label']) . '</div>';
  }
  $build['demonstration'] = array(
    '#type' => 'markup',
    '#markup' => $instance->renderLayout(TRUE, $regions),
  );
  $build['#attached']['css'][] = drupal_get_path('module', 'layout') . '/layout.admin.css';
  return $build;
}
