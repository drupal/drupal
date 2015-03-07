<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\process\d6\CckFile.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\process\Route;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_cck_file"
 * )
 */
class CckFile extends Route implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($fid, $list, $data) = $value;

    // If $fid is still an array at this point, that's because we have a file
    // attachment as per D6 core. If not, then we have a filefield from contrib.
    if (is_array($fid)) {
      $list = $fid['list'];
      $fid = $fid['fid'];
    }
    else {
      $options = unserialize($data);
    }

    $file = [
      'target_id' => $fid,
      'display' => isset($list) ? $list : 0,
      'description' => isset($options['description']) ? $options['description'] : '',
    ];

    return $file;
  }

}
