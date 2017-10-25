<?php

namespace Drupal\Tests\node\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Test D7NodeDeriver.
 *
 * @group migrate_drupal_7
 */
class MigrateNodeDeriverTest extends MigrateDrupal7TestBase {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->pluginManager = $this->container->get('plugin.manager.migration');
    $this->moduleHandler = $this->container->get('module_handler');
  }

  /**
   * Test node translation migrations with translation disabled.
   */
  public function testNoTranslations() {
    // Enabling node module for this test.
    $this->enableModules(['node']);
    // Without content_translation, there should be no translation migrations.
    $migrations = $this->pluginManager->createInstances('d7_node_translation');
    $this->assertTrue($this->moduleHandler->moduleExists('node'));
    $this->assertEmpty($migrations);
  }

  /**
   * Test node translation migrations with translation enabled.
   */
  public function testTranslations() {
    // With content_translation, there should be translation migrations for
    // each content type.
    $this->enableModules(['language', 'content_translation', 'node', 'filter']);
    $migrations = $this->pluginManager->createInstances('d7_node_translation');
    $this->assertArrayHasKey('d7_node_translation:article', $migrations,
      "Node translation migrations exist after content_translation installed");
  }

}
