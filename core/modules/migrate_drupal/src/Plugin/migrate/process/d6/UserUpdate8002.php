<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\process\d6\UserUpdate8002.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;
use Drupal\migrate\MigrateExecutable;
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
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    $rid = $row->getSourceProperty('rid');
    $map = array(
      1 => 'anonymous',
      2 => 'authenticated',
    );
    return isset($map[$rid]) ? $map[$rid] : $value;
  }

}
