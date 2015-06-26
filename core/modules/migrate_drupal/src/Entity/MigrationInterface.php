<?php
/**
 * @file
 * Contains \Drupal\migrate_drupal\Entity\MigrationInterface.
 */

namespace Drupal\migrate_drupal\Entity;

use Drupal\migrate\Entity\MigrationInterface as BaseMigrationInterface;

interface MigrationInterface extends BaseMigrationInterface {

  /**
   * Returns the initialized load plugin if there's one.
   *
   * @return \Drupal\migrate_drupal\Plugin\MigrateLoadInterface|false
   */
  public function getLoadPlugin();

}
