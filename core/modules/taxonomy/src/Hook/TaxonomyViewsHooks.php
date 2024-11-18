<?php

namespace Drupal\taxonomy\Hook;

use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for taxonomy.
 */
class TaxonomyViewsHooks {

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(&$data): void {
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

  /**
   * Implements hook_field_views_data_alter().
   *
   * Views integration for entity reference fields which reference taxonomy terms.
   * Adds a term relationship to the default field data.
   *
   * @see views_field_default_views_data()
   */
  #[Hook('field_views_data_alter')]
  public function fieldViewsDataAlter(array &$data, FieldStorageConfigInterface $field_storage): void {
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
