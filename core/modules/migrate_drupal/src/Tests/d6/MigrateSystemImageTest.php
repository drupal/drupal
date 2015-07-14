<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemImageTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

/**
 * Upgrade image variables to system.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemImageTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d6_system_image');
  }

  /**
   * Tests migration of system (image) variables to system.image.yml.
   */
  public function testSystemImage() {
    $config = $this->config('system.image');
    $this->assertIdentical('gd', $config->get('toolkit'));
  }

}
