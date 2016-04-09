<?php

namespace Drupal\Tests\system\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade image gd variables to system.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateSystemImageGdTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_system_image_gd');
  }

  /**
   * Tests migration of system (image GD) variables to system.image.gd.yml.
   */
  public function testSystemImageGd() {
    $config = $this->config('system.image.gd');
    $this->assertIdentical(75, $config->get('jpeg_quality'));
  }

}
