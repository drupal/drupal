<?php

/**
 * @file
 * Post update functions for Custom Block.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Adds a 'reusable' filter to all Custom Block views.
 */
function block_content_post_update_add_views_reusable_filter(&$sandbox = NULL) {
  $entity_type = \Drupal::entityTypeManager()->getDefinition('block_content');
  $storage = \Drupal::entityTypeManager()->getStorage('block_content');

  // If the storage class is an instance SqlContentEntityStorage we can use it
  // to determine the table to use, otherwise we have to get the table from the
  // entity type.
  if ($storage instanceof SqlContentEntityStorage) {
    $table = $entity_type->isTranslatable() ? $storage->getDataTable() : $storage->getBaseTable();
  }
  else {
    $table = $entity_type->isTranslatable() ? $entity_type->getDataTable() : $entity_type->getBaseTable();
  }
  // If we were not able to get a table name we can not update the views.
  if (empty($table)) {
    return;
  }

  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function ($view) use ($table) {
    /** @var \Drupal\views\ViewEntityInterface $view */
    if ($view->get('base_table') !== $table) {
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
          'table' => $table,
          'field' => 'reusable',
          'relationship' => 'none',
          'group_type' => 'group',
          'admin_label' => '',
          'operator' => '=',
          'value' => '1',
          'group' => 1,
          'exposed' => FALSE,
          'expose' => [
            'operator_id' => '',
            'label' => '',
            'description' => '',
            'use_operator' => FALSE,
            'operator' => '',
            'identifier' => '',
            'required' => FALSE,
            'remember' => FALSE,
            'multiple' => FALSE,
          ],
          'is_grouped' => FALSE,
          'group_info' => [
            'label' => '',
            'description' => '',
            'identifier' => '',
            'optional' => TRUE,
            'widget' => 'select',
            'multiple' => FALSE,
            'remember' => FALSE,
            'default_group' => 'All',
            'default_group_multiple' => [],
            'group_items' => [],
          ],
          'entity_type' => 'block_content',
          'entity_field' => 'reusable',
          'plugin_id' => 'boolean',
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
