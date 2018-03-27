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
<<<<<<< HEAD
   * {@inheritdoc}
   */
  public static $modules = ['node'];
=======
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
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd

  /**
   * Test node translation migrations with translation disabled.
   */
  public function testNoTranslations() {
    // Enabling node module for this test.
    $this->enableModules(['node']);
    // Without content_translation, there should be no translation migrations.
<<<<<<< HEAD
    $migrations = $this->container->get('plugin.manager.migration')->createInstances('d7_node_translation');
=======
    $migrations = $this->pluginManager->createInstances('d7_node_translation');
    $this->assertTrue($this->moduleHandler->moduleExists('node'));
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
    $this->assertEmpty($migrations);
  }

  /**
   * Test node translation migrations with translation enabled.
   */
  public function testTranslations() {
    // With content_translation, there should be translation migrations for
    // each content type.
    $this->enableModules(['language', 'content_translation', 'filter']);
    $this->assertTrue($this->container->get('plugin.manager.migration')->hasDefinition('d7_node_translation:article'), "Node translation migrations exist after content_translation installed");
  }

}
