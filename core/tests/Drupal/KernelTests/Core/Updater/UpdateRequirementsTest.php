<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Updater;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests hook_update_requirements() and hook_update_requirements_alter().
 *
 * @group Hooks
 */
class UpdateRequirementsTest extends KernelTestBase {

  use StringTranslationTrait;

  /**
   * Tests hook_update_requirements().
   */
  public function testUpdateRequirements(): void {
    require_once 'core/includes/update.inc';

    \Drupal::service('module_installer')->install(['module_update_requirements']);
    $testRequirements = [
      'title' => 'UpdateError',
      'value' => 'None',
      'description' => 'Update Error.',
      'severity' => RequirementSeverity::Error,
    ];
    $requirements = update_check_requirements()['test.update.error'];
    $this->assertEquals($testRequirements, $requirements);

    $testAlterRequirements = [
      'title' => 'UpdateWarning',
      'value' => 'None',
      'description' => 'Update Warning.',
      'severity' => RequirementSeverity::Warning,
    ];
    $alterRequirements = update_check_requirements()['test.update.error.alter'];
    $this->assertEquals($testAlterRequirements, $alterRequirements);
  }

}
