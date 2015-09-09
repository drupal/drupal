<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\MigrateTaxonomyConfigsTest.
 */

namespace Drupal\taxonomy\Tests\Migrate;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to taxonomy.settings.yml.
 *
 * @group migrate_drupal_6
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
    $this->executeMigration('taxonomy_settings');
  }

  /**
   * Tests migration of taxonomy variables to taxonomy.settings.yml.
   */
  public function testTaxonomySettings() {
    $config = $this->config('taxonomy.settings');
    $this->assertIdentical(100, $config->get('terms_per_page_admin'));
    $this->assertIdentical(FALSE, $config->get('override_selector'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'taxonomy.settings', $config->get());
  }

}
