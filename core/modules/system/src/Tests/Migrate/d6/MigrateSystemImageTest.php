<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Migrate\d6\MigrateSystemImageTest.
 */

namespace Drupal\system\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade image variables to system.*.yml.
 *
 * @group system
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
