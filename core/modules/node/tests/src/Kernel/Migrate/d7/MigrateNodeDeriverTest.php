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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->pluginManager = $this->container->get('plugin.manager.migration');
  }

  /**
   * Test node translation migrations with translation disabled.
   */
  public function testNoTranslations() {
    // Without content_translation, there should be no translation migrations.
    $migrations = $this->pluginManager->createInstances('d7_node_translation');
    $this->assertSame([], $migrations,
      "No node translation migrations without content_translation");
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
