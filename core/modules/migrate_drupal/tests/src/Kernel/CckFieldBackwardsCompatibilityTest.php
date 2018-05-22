<?php

namespace Drupal\Tests\migrate_drupal\Kernel;

use Drupal\migrate_cckfield_plugin_manager_test\Plugin\migrate\cckfield\D6FileField;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * @group migrate_drupal
 * @group legacy
 */
class CckFieldBackwardsCompatibilityTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file', 'migrate_cckfield_plugin_manager_test'];

  /**
   * Ensures that the cckfield backwards compatibility layer is invoked.
   *
   * @expectedDeprecation MigrateCckFieldInterface is deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.x. Use \Drupal\migrate_drupal\Annotation\MigrateField instead.
   */
  public function testBackwardsCompatibility() {
    $migration = $this->container
      ->get('plugin.manager.migration')
      ->getDefinition('d6_node:story');

    $this->assertSame(D6FileField::class, $migration['process']['field_test_filefield']['class']);
  }

}
