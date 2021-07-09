<?php

/**
 * @file
 * Provide views data for node.module.
 */

use Drupal\field\FieldStorageConfigInterface;
use Drupal\user\RoleInterface;
use Drupal\views\ViewExecutable;
use Drupal\user\Entity\Role;
use Drupal\views\Analyzer;

/**
 * Implements hook_views_analyze().
 */
function node_views_analyze(ViewExecutable $view) {
  $ret = [];
  // Check for something other than the default display:
  if ($view->storage->get('base_table') == 'node') {
    foreach ($view->displayHandlers as $display) {
      if (!$display->isDefaulted('access') || !$display->isDefaulted('filters')) {
        // check for no access control
        $access = $display->getOption('access');
        if (empty($access['type']) || $access['type'] == 'none') {
          $anonymous_role = Role::load(RoleInterface::ANONYMOUS_ID);
          $anonymous_has_access = $anonymous_role && $anonymous_role->hasPermission('access content');
          $authenticated_role = Role::load(RoleInterface::AUTHENTICATED_ID);
          $authenticated_has_access = $authenticated_role && $authenticated_role->hasPermission('access content');
          if (!$anonymous_has_access || !$authenticated_has_access) {
            $ret[] = Analyzer::formatMessage(t('Some roles lack permission to access content, but display %display has no access control.', ['%display' => $display->display['display_title']]), 'warning');
          }
          $filters = $display->getOption('filters');
          foreach ($filters as $filter) {
            if ($filter['table'] == 'node' && ($filter['field'] == 'status' || $filter['field'] == 'status_extra')) {
              continue 2;
            }
          }
          $ret[] = Analyzer::formatMessage(t('Display %display has no access control but does not contain a filter for published nodes.', ['%display' => $display->display['display_title']]), 'warning');
        }
      }
    }
  }
  foreach ($view->displayHandlers as $display) {
    if ($display->getPluginId() == 'page') {
      if ($display->getOption('path') == 'node/%') {
        $ret[] = Analyzer::formatMessage(t('Display %display has set node/% as path. This will not produce what you want. If you want to have multiple versions of the node view, use Layout Builder.', ['%display' => $display->display['display_title']]), 'warning');
      }
    }
  }

  return $ret;
}

/**
 * Implements hook_views_data_alter().
 */
function node_views_data_alter(&$data) {
  if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
    $data['node_field_data']['term_node_tid'] = [
      'title' => t('Taxonomy terms on node'),
      'help' => t('Relate nodes to taxonomy terms, specifying which vocabulary or vocabularies to use. This relationship will cause duplicated records if there are multiple terms.'),
      'relationship' => [
        'id' => 'node_term_data',
        'label' => t('term'),
        'base' => 'taxonomy_term_field_data',
      ],
      'field' => [
        'title' => t('All taxonomy terms'),
        'help' => t('Display all taxonomy terms associated with a node from specified vocabularies.'),
        'id' => 'taxonomy_index_tid',
        'no group by' => TRUE,
        'click sortable' => FALSE,
      ],
    ];

    $data['node_field_data']['term_node_tid_depth'] = [
      'help' => t('Display content if it has the selected taxonomy terms, or children of the selected terms. Due to additional complexity, this has fewer options than the versions without depth.'),
      'real field' => 'nid',
      'argument' => [
        'title' => t('Has taxonomy term ID (with depth)'),
        'id' => 'taxonomy_index_tid_depth',
        'accept depth modifier' => TRUE,
      ],
      'filter' => [
        'title' => t('Has taxonomy terms (with depth)'),
        'id' => 'taxonomy_index_tid_depth',
      ],
    ];

    $data['node_field_data']['term_node_tid_depth_modifier'] = [
      'title' => t('Has taxonomy term ID depth modifier'),
      'help' => t('Allows the "depth" for Taxonomy: Term ID (with depth) to be modified via an additional contextual filter value.'),
      'argument' => [
        'id' => 'taxonomy_index_tid_depth_modifier',
      ],
    ];
  }
}

/**
 * Implements hook_field_views_data_alter().
 *
 * Views integration for entity reference fields which reference taxonomy terms.
 * Adds a term relationship to the default field data.
 *
 * @see views_field_default_views_data()
 */
function node_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
    if ($field_storage->getType() == 'entity_reference' && $field_storage->getSetting('target_type') == 'taxonomy_term') {
      foreach ($data as $table_name => $table_data) {
        foreach ($table_data as $field_name => $field_data) {
          if (isset($field_data['filter']) && $field_name != 'delta') {
            $data[$table_name][$field_name]['filter']['id'] = 'taxonomy_index_tid';
          }
        }
      }
    }
  }
}
