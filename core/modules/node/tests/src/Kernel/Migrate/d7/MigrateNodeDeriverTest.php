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
   * {@inheritdoc}
   */
  public static $modules = ['node'];

  /**
   * Test node translation migrations with translation disabled.
   */
  public function testNoTranslations() {
    // Without content_translation, there should be no translation migrations.
    $migrations = $this->container->get('plugin.manager.migration')->createInstances('d7_node_translation');
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
