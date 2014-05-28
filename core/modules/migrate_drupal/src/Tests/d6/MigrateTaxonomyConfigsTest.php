<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateTaxonomyConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of variables from the Taxonomy module.
 */
class MigrateTaxonomyConfigsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to taxonomy.settings.yml',
      'description'  => 'Upgrade variables to taxonomy.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_taxonomy_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6TaxonomySettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of taxonomy variables to taxonomy.settings.yml.
   */
  public function testTaxonomySettings() {
    $config = \Drupal::config('taxonomy.settings');
    $this->assertIdentical($config->get('terms_per_page_admin'), 100);
    $this->assertIdentical($config->get('override_selector'), FALSE);
  }
}
