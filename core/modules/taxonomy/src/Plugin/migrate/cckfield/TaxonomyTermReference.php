<?php

namespace Drupal\taxonomy\Plugin\migrate\cckfield;

@trigger_error('TaxonomyTermReference is deprecated in Drupal 8.4.x and will be removed before Drupal 9.0.x. Use \Drupal\taxonomy\Plugin\migrate\field\TaxonomyTermReference instead.', E_USER_DEPRECATED);

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\cckfield\CckFieldPluginBase;

/**
 * @MigrateCckField(
 *   id = "taxonomy_term_reference",
 *   type_map = {
 *     "taxonomy_term_reference" = "entity_reference"
 *   },
 *   core = {6,7},
 *   source_module = "taxonomy",
 *   destination_module = "core",
 * )
 *
 * @deprecated in Drupal 8.4.x, to be removed before Drupal 9.0.x. Use
 * \Drupal\taxonomy\Plugin\migrate\field\TaxonomyTermReference instead.
 *
 * @see https://www.drupal.org/node/2751897
 */
class TaxonomyTermReference extends CckFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {
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
