<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Updater;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Update\DatabaseUpdate;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests hook_update_requirements() and hook_update_requirements_alter().
 */
#[Group('Hooks')]
#[RunTestsInSeparateProcesses]
class UpdateRequirementsTest extends KernelTestBase {

  use StringTranslationTrait;

  /**
   * Tests hook_update_requirements().
   */
  public function testUpdateRequirements(): void {
    \Drupal::service('module_installer')->install(['module_update_requirements']);
    // Installing a module rebuilds the container, so get the service after.
    $databaseUpdate = \Drupal::service(DatabaseUpdate::class);
    $testRequirements = [
      'title' => 'UpdateError',
      'value' => 'None',
      'description' => 'Update Error.',
      'severity' => RequirementSeverity::Error,
    ];
    $requirements = $databaseUpdate->getRequirements()['test.update.error'];
    $this->assertEquals($testRequirements, $requirements);

    $testAlterRequirements = [
      'title' => 'UpdateWarning',
      'value' => 'None',
      'description' => 'Update Warning.',
      'severity' => RequirementSeverity::Warning,
    ];
    $alterRequirements = $databaseUpdate->getRequirements()['test.update.error.alter'];
    $this->assertEquals($testAlterRequirements, $alterRequirements);
  }

}
