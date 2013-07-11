<?php

/**
 * @file
 * Administration functions for layouts.
 */

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
  $layout = Drupal::service('plugin.manager.layout')->getDefinition($key);
  drupal_set_title(t('View template %name', array('%name' => $layout['title'])), PASS_THROUGH);

  // Render the layout in an admin context with region demonstrations.
  $instance = Drupal::service('plugin.manager.layout')->createInstance($key, array());
  $regions = $instance->getRegions();
  foreach ($regions as $region => $info) {
    $regions[$region] = '<div class="layout-region-demonstration">' . check_plain($info['label']) . '</div>';
  }
  $build['demonstration'] = array(
    '#markup' => $instance->renderLayout(TRUE, $regions),
  );
  $build['#attached']['css'][] = drupal_get_path('module', 'layout') . '/css/layout.admin.css';
  return $build;
}
