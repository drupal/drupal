<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Migrate\d6\MigrateSystemImageGdTest.
 */

namespace Drupal\system\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade image gd variables to system.*.yml.
 *
 * @group system
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
