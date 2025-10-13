<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal\Kernel\d6;

use Drupal\migrate\Exception\RequirementsException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests check requirements for variable translation source plugin.
 */
#[Group('migrate_drupal')]
#[RunTestsInSeparateProcesses]
class VariableTranslationCheckRequirementsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->sourceDatabase->schema()->dropTable('i18n_variable');
  }

  /**
   * Tests exception in thrown when the i18n_variable table does not exist.
   */
  public function testCheckRequirements(): void {
    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage("Source database table 'i18n_variable' does not exist");
    $this->getMigration('d6_system_maintenance_translation')
      ->getSourcePlugin()
      ->checkRequirements();
  }

}
