<?php

namespace Drupal\Tests\migrate_drupal\Traits;

trait CreateMigrationsTrait {

  /**
   * Create instances of all Drupal 6 migrations.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface[]
   *   The migrations
   */
  public function drupal6Migrations() {
    $dirs = \Drupal::service('module_handler')->getModuleDirectories();
    $migrate_drupal_directory = $dirs['migrate_drupal'];
    $this->loadFixture("$migrate_drupal_directory/tests/fixtures/drupal6.php");
    return \Drupal::service('plugin.manager.migration')->createInstancesByTag('Drupal 6');
  }

  /**
   * Create instances of all Drupal 7 migrations.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface[]
   *   The migrations
   */
  public function drupal7Migrations() {
    $dirs = \Drupal::service('module_handler')->getModuleDirectories();
    $migrate_drupal_directory = $dirs['migrate_drupal'];
    $this->loadFixture("$migrate_drupal_directory/tests/fixtures/drupal7.php");
    return \Drupal::service('plugin.manager.migration')->createInstancesByTag('Drupal 7');
  }

}
