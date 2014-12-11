<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\process\d6\BlockVisibility.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_block_visibility"
 * )
 */
class BlockVisibility extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Set the block visibility settings.
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    list($pages, $roles, $old_visibility) = $value;
    $visibility = array();
    $visibility['request_path']['pages'] = $pages;
    $visibility['request_path']['id'] = 'request_path';
    $visibility['request_path']['negate'] = !$old_visibility;

    if (!empty($roles)) {
      $visibility['user_role']['roles'] = $roles;
      $visibility['user_role']['id'] = 'user_role';
      $visibility['user_role']['context_mapping']['user'] = 'user.current_user';
    }
    return $visibility;
  }

}
