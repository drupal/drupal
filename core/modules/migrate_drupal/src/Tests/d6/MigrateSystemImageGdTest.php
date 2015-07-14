<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemImageGdTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

/**
 * Upgrade image gd variables to system.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemImageGdTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps(['Variable.php']);
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
