<?php

/**
 * @file
 * Post update functions for Node.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\views\Entity\View;

/**
* Load all form displays for nodes, add status with these settings, save.
*/
function node_post_update_configure_status_field_widget() {
  $query = \Drupal::entityQuery('entity_form_display')->condition('targetEntityType', 'node');
  $ids = $query->execute();
  $form_displays = EntityFormDisplay::loadMultiple($ids);

  // Assign status settings for each 'node' target entity types with 'default'
  // form mode.
  foreach ($form_displays as $id => $form_display) {
    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $form_display */
    $form_display->setComponent('status', [
      'type' => 'boolean_checkbox',
      'settings' => [
        'display_label' => TRUE,
      ],
    ])->save();
  }
}

/**
 * Clear caches due to updated views data.
 */
function node_post_update_node_revision_views_data() {
  // Empty post-update hook.
}

/**
 * Add a published filter to the glossary View.
 */
function node_post_update_glossary_view_published() {
  if (\Drupal::moduleHandler()->moduleExists('views')) {
    $view = View::load('glossary');
    if (!$view) {
      return;
    }
    $display =& $view->getDisplay('default');
    if (!isset($display['display_options']['filters']['status'])) {
      $display['display_options']['filters']['status'] = [
        'expose' => [
          'operator' => '',
          'operator_limit_selection' => FALSE,
          'operator_list' => [],
        ],
        'field' => 'status',
        'group' => 1,
        'id' => 'status',
        'table' => 'node_field_data',
        'value' => '1',
        'plugin_id' => 'boolean',
        'entity_type' => 'node',
        'entity_field' => 'status',
      ];
      $view->save();
    }
  }
}
