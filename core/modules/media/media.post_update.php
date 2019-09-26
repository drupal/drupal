<?php

/**
 * @file
 * Post update functions for Media.
 */

use Drupal\user\RoleInterface;
use Drupal\views\Views;

/**
 * Clear caches due to changes in local tasks and action links.
 */
function media_post_update_collection_route() {
  // Empty post-update hook.
}

/**
 * Clear caches due to the addition of a Media-specific entity storage handler.
 */
function media_post_update_storage_handler() {
  // Empty post-update hook.
}

/**
 * Keep media items viewable at /media/{id}.
 */
function media_post_update_enable_standalone_url() {
  $config = \Drupal::configFactory()->getEditable('media.settings');
  if ($config->get('standalone_url') === NULL) {
    $config->set('standalone_url', TRUE)->save(TRUE);
  }
}

/**
 * Add a status extra filter to the media view default display.
 */
function media_post_update_add_status_extra_filter() {
  $view = Views::getView('media');

  if (!$view) {
    return;
  }

  // Fetch the filters from the default display and add the new 'status_extra'
  // filter if it does not yet exist.
  $default_display = $view->getDisplay();
  $filters = $default_display->getOption('filters');

  if (!isset($filters['status_extra'])) {
    $filters['status_extra'] = [
      'group_info' => [
        'widget' => 'select',
        'group_items' => [],
        'multiple' => FALSE,
        'description' => '',
        'default_group_multiple' => [],
        'default_group' => 'All',
        'label' => '',
        'identifier' => '',
        'optional' => TRUE,
        'remember' => FALSE,
      ],
      'group' => 1,
      'relationship' => 'none',
      'exposed' => FALSE,
      'expose' => [
        'use_operator' => FALSE,
        'remember' => FALSE,
        'operator_id' => '',
        'multiple' => FALSE,
        'description' => '',
        'required' => FALSE,
        'label' => '',
        'operator_limit_selection' => FALSE,
        'operator' => '',
        'identifier' => '',
        'operator_list' => [],
        'remember_roles' => [RoleInterface::AUTHENTICATED_ID => RoleInterface::AUTHENTICATED_ID],
      ],
      'entity_type' => 'media',
      'value' => '',
      'field' => 'status_extra',
      'is_grouped' => FALSE,
      'admin_label' => '',
      'operator' => '=',
      'table' => 'media_field_data',
      'plugin_id' => 'media_status',
      'id' => 'status_extra',
      'group_type' => 'group',
    ];
    $default_display->setOption('filters', $filters);
    $view->save();

    return t("The 'Published status or admin user' filter was added to the %label view.", [
      '%label' => $view->storage->label(),
    ]);
  }
}
