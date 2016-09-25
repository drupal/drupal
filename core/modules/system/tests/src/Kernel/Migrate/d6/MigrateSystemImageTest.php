<?php

namespace Drupal\Tests\system\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade image variables to system.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateSystemImageTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('system_image');
  }

  /**
   * Tests migration of system (image) variables to system.image.yml.
   */
  public function testSystemImage() {
    $config = $this->config('system.image');
    $this->assertIdentical('gd', $config->get('toolkit'));
  }

}
