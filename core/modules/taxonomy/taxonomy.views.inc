<?php

/**
 * @file
 * Provides views data for taxonomy.module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data_alter().
 */
function taxonomy_views_data_alter(&$data) {
  $data['node_field_data']['term_node_tid'] = array(
    'title' => t('Taxonomy terms on node'),
    'help' => t('Relate nodes to taxonomy terms, specifying which vocabulary or vocabularies to use. This relationship will cause duplicated records if there are multiple terms.'),
    'relationship' => array(
      'id' => 'node_term_data',
      'label' => t('term'),
      'base' => 'taxonomy_term_field_data',
    ),
    'field' => array(
      'title' => t('All taxonomy terms'),
      'help' => t('Display all taxonomy terms associated with a node from specified vocabularies.'),
      'id' => 'taxonomy_index_tid',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ),
  );

  $data['node_field_data']['term_node_tid_depth'] = array(
    'help' => t('Display content if it has the selected taxonomy terms, or children of the selected terms. Due to additional complexity, this has fewer options than the versions without depth.'),
    'real field' => 'nid',
    'argument' => array(
      'title' => t('Has taxonomy term ID (with depth)'),
      'id' => 'taxonomy_index_tid_depth',
      'accept depth modifier' => TRUE,
    ),
    'filter' => array(
      'title' => t('Has taxonomy terms (with depth)'),
      'id' => 'taxonomy_index_tid_depth',
    ),
  );

  $data['node_field_data']['term_node_tid_depth_modifier'] = array(
    'title' => t('Has taxonomy term ID depth modifier'),
    'help' => t('Allows the "depth" for Taxonomy: Term ID (with depth) to be modified via an additional contextual filter value.'),
    'argument' => array(
      'id' => 'taxonomy_index_tid_depth_modifier',
    ),
  );
}

/**
 * Implements hook_field_views_data_alter().
 *
 * Views integration for entity reference fields which reference taxonomy terms.
 * Adds a term relationship to the default field data.
 *
 * @see views_field_default_views_data()
 */
function taxonomy_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
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

/**
 * Implements hook_views_plugins_argument_validator_alter().
 *
 * Extend the generic entity argument validator.
 *
 * @see \Drupal\views\Plugin\views\argument_validator\Entity
 */
function taxonomy_views_plugins_argument_validator_alter(array &$plugins) {
  $plugins['entity:taxonomy_term']['title'] = t('Taxonomy term ID');
  $plugins['entity:taxonomy_term']['class'] = 'Drupal\taxonomy\Plugin\views\argument_validator\Term';
  $plugins['entity:taxonomy_term']['provider'] = 'taxonomy';
}
