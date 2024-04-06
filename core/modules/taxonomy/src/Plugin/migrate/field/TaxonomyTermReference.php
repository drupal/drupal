<?php

namespace Drupal\taxonomy\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

// cspeLL:ignore entityreference
/**
 * MigrateField Plugin for Drupal 6 & Drupal 7 taxonomy term reference fields.
 */
#[MigrateField(
  id: 'taxonomy_term_reference',
  core: [6, 7],
  type_map: [
    'taxonomy_term_reference' => 'entity_reference',
  ],
  source_module: 'taxonomy',
  destination_module: 'core',
)]
class TaxonomyTermReference extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'taxonomy_term_reference_link' => 'entity_reference_label',
      'taxonomy_term_reference_plain' => 'entity_reference_label',
      'taxonomy_term_reference_rss_category' => 'entity_reference_label',
      'i18n_taxonomy_term_reference_link' => 'entity_reference_label',
      'i18n_taxonomy_term_reference_plain' => 'entity_reference_label',
      'entityreference_entity_view' => 'entity_reference_entity_view',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'target_id' => 'tid',
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

}
