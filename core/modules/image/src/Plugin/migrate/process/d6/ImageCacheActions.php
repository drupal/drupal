<?php

namespace Drupal\image\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_imagecache_actions"
 * )
 */
class ImageCacheActions extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $effects = [];

    foreach($row->getSourceProperty('actions') as $action) {
      $id = preg_replace('/^imagecache/', 'image', $action['action']);

      if ($id === 'image_crop') {
        $action['data']['anchor'] = $action['data']['xoffset'] . '-' . $action['data']['yoffset'];

        if (!preg_match('/^[a-z]*\-[a-z]*/', $action['data']['anchor'])) {
          $migrate_executable->message->display(
            'The Drupal 8 image crop effect does not support numeric values for x and y offsets. Use keywords to set crop effect offsets instead.',
            'error'
          );
        }

        unset($action['data']['xoffset']);
        unset($action['data']['yoffset']);
      }

      $effects[] = [
        'id'     => $id,
        'weight' => $action['weight'],
        'data'   => $action['data'],
      ];
    }

    return $effects;
  }

}
