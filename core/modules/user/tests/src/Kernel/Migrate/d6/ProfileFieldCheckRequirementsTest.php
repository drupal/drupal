<?php

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\migrate\Exception\RequirementsException;

/**
 * Tests check requirements for profile_field source plugin.
 *
 * @group user
 */
class ProfileFieldCheckRequirementsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->sourceDatabase->schema()->dropTable('profile_fields');
  }

  /**
   * Tests exception is thrown when profile_fields tables do not exist.
   */
  public function testCheckRequirements() {
    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage('Profile module not enabled on source site');
    $this->getMigration('user_profile_field')
      ->getSourcePlugin()
      ->checkRequirements();
  }

}
