<?php

namespace Drupal\file\Plugin\migrate\field\d7;

<<<<<<< HEAD
@trigger_error('ImageField is deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.x. Use \Drupal\image\Plugin\migrate\field\d7\ImageField instead. See https://www.drupal.org/node/2936061.', E_USER_DEPRECATED);

use Drupal\image\Plugin\migrate\field\d7\ImageField as NonLegacyImageField;

/**
 * Field plugin for image fields.
 *
 * @deprecated in Drupal 8.5.x, to be removed before Drupal 9.0.x. Use
 * \Drupal\image\Plugin\migrate\field\d7\ImageField instead.
 *
 * @see https://www.drupal.org/node/2936061
 */
class ImageField extends NonLegacyImageField {}
=======
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "image",
 *   core = {7}
 * )
 */
class ImageField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
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
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
