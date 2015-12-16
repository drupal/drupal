<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\migrate\cckfield\TaxonomyTermReference.
 */

namespace Drupal\taxonomy\Plugin\migrate\cckfield;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\cckfield\CckFieldPluginBase;

/**
 * @MigrateCckField(
 *   id = "taxonomy_term_reference"
 * )
 */
class TaxonomyTermReference extends CckFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = array(
      'plugin' => 'iterator',
      'source' => $field_name,
      'process' => array(
        'target_id' => 'tid',
      ),
    );
    $migration->setProcessOfProperty($field_name, $process);
  }

}
