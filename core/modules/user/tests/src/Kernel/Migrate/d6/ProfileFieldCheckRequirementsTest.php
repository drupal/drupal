<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests check requirements for profile_field source plugin.
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
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
  public function testCheckRequirements(): void {
    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage('Profile module not enabled on source site');
    $this->getMigration('user_profile_field')
      ->getSourcePlugin()
      ->checkRequirements();
  }

}
