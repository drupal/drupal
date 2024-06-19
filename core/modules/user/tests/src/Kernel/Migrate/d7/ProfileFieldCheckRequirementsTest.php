<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\migrate\Exception\RequirementsException;

/**
 * Tests check requirements for profile_field source plugin.
 *
 * @group user
 */
class ProfileFieldCheckRequirementsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->sourceDatabase->schema()->dropTable('profile_field');
  }

  /**
   * Tests exception is thrown when profile_fields tables do not exist.
   */
  public function testCheckRequirements(): void {
    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage('Profile module not enabled on source site');
    $this->getMigration('user_profile_field')
      ->getSourcePlugin()
      ->checkRequirements();
  }

}
