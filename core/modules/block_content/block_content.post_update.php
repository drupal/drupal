<?php

/**
 * @file
 * Post update functions for Custom Block.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Adds a 'reusable' filter to all Custom Block views.
 */
function block_content_post_update_add_views_reusable_filter(&$sandbox = NULL) {
  $data_table = \Drupal::entityTypeManager()
    ->getDefinition('block_content')
    ->getDataTable();

  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function ($view) use ($data_table) {
    /** @var \Drupal\views\ViewEntityInterface $view */
    if ($view->get('base_table') != $data_table) {
      return FALSE;
    }
    $save_view = FALSE;
    $displays = $view->get('display');
    foreach ($displays as $display_name => &$display) {
      // Update the default display and displays that have overridden filters.
      if (!isset($display['display_options']['filters']['reusable']) &&
        ($display_name === 'default' || isset($display['display_options']['filters']))) {
        $display['display_options']['filters']['reusable'] = [
          'id' => 'reusable',
          'plugin_id' => 'boolean',
          'table' => $data_table,
          'field' => 'reusable',
          'value' => '1',
          'entity_type' => 'block_content',
          'entity_field' => 'reusable',
        ];
        $save_view = TRUE;
      }
    }
    if ($save_view) {
      $view->set('display', $displays);
    }
    return $save_view;
  });
}
