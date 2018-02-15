<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\ban\Plugin\migrate\destination\BlockedIP;
use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\migrate\Plugin\migrate\destination\ComponentEntityDisplayBase;
use Drupal\migrate\Plugin\migrate\destination\Config;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\path\Plugin\migrate\destination\UrlAlias;
use Drupal\shortcut\Plugin\migrate\destination\ShortcutSetUsers;
use Drupal\statistics\Plugin\migrate\destination\NodeCounter;
use Drupal\system\Plugin\migrate\destination\d7\ThemeSettings;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use Drupal\user\Plugin\migrate\destination\UserData;

/**
 * Tests that all migrations are tagged as either content or configuration.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\MigrationPluginManager
 *
 * @group migrate
 */
class DestinationCategoryTest extends MigrateDrupalTestBase {

  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Enable all modules.
    self::$modules = array_keys($this->coreModuleListDataProvider());
    parent::setUp();
    $this->migrationManager = \Drupal::service('plugin.manager.migration');
  }

  /**
   * @covers ::getDefinitions
   */
  public function testGetGroupedDefinitions() {
    $definitions = array_keys($this->migrationManager->getDefinitions());

    // Configuration migrations should have a destination plugin that is an
    // instance of one of the following classes.
    $config_classes = [
      Config::class,
      EntityConfigBase::class,
      ThemeSettings::class,
      ComponentEntityDisplayBase::class,
      ShortcutSetUsers::class,
    ];
    // Content migrations should have a destination plugin that is an instance
    // of one of the following classes.
    $content_classes = [
      EntityContentBase::class,
      UrlAlias::class,
      BlockedIP::class,
      NodeCounter::class,
      UserData::class,
    ];

    // Instantiate all migrations.
    /** @var  \Drupal\migrate\Plugin\Migration[] $migrations */
    $migrations = $this->migrationManager->createInstances($definitions);
    foreach ($migrations as $id => $migration) {
      $object_classes = class_parents($migration->getDestinationPlugin());
      $object_classes[] = get_class($migration->getDestinationPlugin());

      // Ensure that the destination plugin is an instance of at least one of
      // the expected classes.
      if (in_array('Configuration', $migration->getMigrationTags(), TRUE)) {
        $this->assertNotEmpty(array_intersect($object_classes, $config_classes), "The migration $id is tagged as Configuration.");
      }
      elseif (in_array('Content', $migration->getMigrationTags(), TRUE)) {
        $this->assertNotEmpty(array_intersect($object_classes, $content_classes), "The migration $id is tagged as Content.");
      }
      else {
        $this->fail("The migration $id is not tagged as either 'Content' or 'Configuration'.");
      }
    }
  }

}
