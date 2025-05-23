<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\System;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the effectiveness of hook_runtime_requirements().
 *
 * @group system
 */
class RunTimeRequirementsTest extends KernelTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests hook_runtime_requirements() and hook_runtime_requirements_alter().
   */
  public function testRuntimeRequirements(): void {
    // Enable the test module.
    \Drupal::service('module_installer')->install(['module_runtime_requirements']);
    $testRequirements = [
      'title' => 'RuntimeError',
      'value' => 'None',
      'description' => 'Runtime Error.',
      'severity' => RequirementSeverity::Error,
    ];
    $requirements = \Drupal::service('system.manager')->listRequirements()['test.runtime.error'];
    $this->assertEquals($testRequirements, $requirements);

    $testRequirementsAlter = [
      'title' => 'RuntimeWarning',
      'value' => 'None',
      'description' => 'Runtime Warning.',
      'severity' => RequirementSeverity::Warning,
    ];
    $requirementsAlter = \Drupal::service('system.manager')->listRequirements()['test.runtime.error.alter'];
    $this->assertEquals($testRequirementsAlter, $requirementsAlter);
  }

}
