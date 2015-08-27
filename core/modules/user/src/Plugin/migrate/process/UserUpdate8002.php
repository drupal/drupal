<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\migrate\process\UserUpdate8002.
 */

namespace Drupal\user\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Keep the predefined roles for rid 1 and 2.
 *
 * @MigrateProcessPlugin(
 *   id = "user_update_8002"
 * )
 */
class UserUpdate8002 extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Keep the predefined roles for rid 1 and 2.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $rid = $row->getSourceProperty('rid');
    $map = array(
      1 => 'anonymous',
      2 => 'authenticated',
    );
    return isset($map[$rid]) ? $map[$rid] : $value;
  }

}
