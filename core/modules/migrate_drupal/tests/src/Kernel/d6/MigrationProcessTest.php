<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d6;

use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\Tests\DeprecatedModulesTestTrait;

/**
 * Tests the getProcess() method of all Drupal 6 migrations.
 *
 * @group migrate_drupal
 */
class MigrationProcessTest extends MigrateDrupal6TestBase {

  use DeprecatedModulesTestTrait;
  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    self::$modules = array_keys($this->coreModuleListDataProvider());
    self::$modules = $this->removeDeprecatedModules(self::$modules);
    parent::setUp();
  }

  /**
   * Tests that calling getProcess() on a migration does not throw an exception.
   *
   * @throws \Exception
   */
  public function testGetProcess() {
    /** @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.migration');
    $migrations = $plugin_manager->createInstancesByTag('Drupal 6');
    foreach ($migrations as $migration) {
      try {
        $process = $migration->getProcess();
      }
      catch (\Exception $e) {
        $this->fail(sprintf("Migration %s process failed with error: %s", $migration->label(), $e->getMessage()));
      }
      $this->assertNotNull($process);
    }
  }

}
