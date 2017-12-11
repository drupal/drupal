<?php

namespace Drupal\file\Plugin\migrate\cckfield\d7;

@trigger_error('ImageField is deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.x. Use \Drupal\file\Plugin\migrate\field\d7\ImageField instead.', E_USER_DEPRECATED);

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\cckfield\CckFieldPluginBase;

/**
 * @MigrateCckField(
 *   id = "image",
 *   core = {7}
 * )
 *
 * @deprecated in Drupal 8.3.x, to be removed before Drupal 9.0.x. Use
 * \Drupal\file\Plugin\migrate\field\d7\ImageField instead.
 *
 * @see https://www.drupal.org/node/2751897
 */
class ImageField extends CckFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'target_id' => 'fid',
        'alt' => 'alt',
        'title' => 'title',
        'width' => 'width',
        'height' => 'height',
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
