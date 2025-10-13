<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Migrate;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Upgrade variables to taxonomy.settings.yml.
 */
#[Group('migrate_drupal_6')]
#[RunTestsInSeparateProcesses]
class MigrateTaxonomyConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('taxonomy_settings');
  }

  /**
   * Tests migration of taxonomy variables to taxonomy.settings.yml.
   */
  public function testTaxonomySettings(): void {
    $config = $this->config('taxonomy.settings');
    $this->assertSame(100, $config->get('terms_per_page_admin'));
    $this->assertFalse($config->get('override_selector'));
    $this->assertTrue($config->get('maintain_index_table'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'taxonomy.settings', $config->get());
  }

}
