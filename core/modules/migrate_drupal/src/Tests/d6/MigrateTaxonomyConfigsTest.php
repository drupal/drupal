<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateTaxonomyConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to taxonomy.settings.yml.
 *
 * @group migrate_drupal
 */
class MigrateTaxonomyConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_taxonomy_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of taxonomy variables to taxonomy.settings.yml.
   */
  public function testTaxonomySettings() {
    $config = $this->config('taxonomy.settings');
    $this->assertIdentical($config->get('terms_per_page_admin'), 100);
    $this->assertIdentical($config->get('override_selector'), FALSE);
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'taxonomy.settings', $config->get());
  }

}
