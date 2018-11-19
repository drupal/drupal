<?php

/**
 * @file
 * Post update functions for Node.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;

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
