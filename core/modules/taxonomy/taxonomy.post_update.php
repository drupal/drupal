<?php

/**
 * @file
 * Post update functions for Taxonomy.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\views\ViewExecutable;

/**
 * Clear caches due to updated taxonomy entity views data.
 */
function taxonomy_post_update_clear_views_data_cache() {
  // An empty update will flush caches.
}

/**
 * Clear entity_bundle_field_definitions cache for new parent field settings.
 */
function taxonomy_post_update_clear_entity_bundle_field_definitions_cache() {
  // An empty update will flush caches.
}

/**
 * Add a 'published' = TRUE filter for all Taxonomy term views and converts
 * existing ones that were using the 'content_translation_status' field.
 */
function taxonomy_post_update_handle_publishing_status_addition_in_views(&$sandbox = NULL) {
  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type = $definition_update_manager->getEntityType('taxonomy_term');
  $published_key = $entity_type->getKey('published');

  $status_filter = [
    'id' => 'status',
    'table' => 'taxonomy_term_field_data',
    'field' => $published_key,
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
      'remember_roles' => [
        'authenticated' => 'authenticated',
        'anonymous' => '0',
        'administrator' => '0',
      ],
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
    'entity_type' => 'taxonomy_term',
    'entity_field' => $published_key,
    'plugin_id' => 'boolean',
  ];

  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function ($view) use ($published_key, $status_filter) {
    /** @var \Drupal\views\ViewEntityInterface $view */
    // Only alter taxonomy term views.
    if ($view->get('base_table') !== 'taxonomy_term_field_data') {
      return FALSE;
    }

    $displays = $view->get('display');
    foreach ($displays as $display_name => &$display) {
      // Update any existing 'content_translation_status fields.
      $fields = isset($display['display_options']['fields']) ? $display['display_options']['fields'] : [];
      foreach ($fields as $id => $field) {
        if (isset($field['field']) && $field['field'] == 'content_translation_status') {
          $fields[$id]['field'] = $published_key;
        }
      }
      $display['display_options']['fields'] = $fields;

      // Update any existing 'content_translation_status sorts.
      $sorts = isset($display['display_options']['sorts']) ? $display['display_options']['sorts'] : [];
      foreach ($sorts as $id => $sort) {
        if (isset($sort['field']) && $sort['field'] == 'content_translation_status') {
          $sorts[$id]['field'] = $published_key;
        }
      }
      $display['display_options']['sorts'] = $sorts;

      // Update any existing 'content_translation_status' filters or add a new
      // one if necessary.
      $filters = isset($display['display_options']['filters']) ? $display['display_options']['filters'] : [];
      $has_status_filter = FALSE;
      foreach ($filters as $id => $filter) {
        if (isset($filter['field']) && $filter['field'] == 'content_translation_status') {
          $filters[$id]['field'] = $published_key;
          $has_status_filter = TRUE;
        }
      }

      if (!$has_status_filter) {
        $status_filter['id'] = ViewExecutable::generateHandlerId($published_key, $filters);
        $filters[$status_filter['id']] = $status_filter;
      }
      $display['display_options']['filters'] = $filters;
    }
    $view->set('display', $displays);

    return TRUE;
  });
}
