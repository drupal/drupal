<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Module;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Covers hook_requirements and hook_requirements_alter.
 */
#[Group('Module')]
#[RunTestsInSeparateProcesses]
class RequirementsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'requirements1_test',
    'system',
  ];

  /**
   * Tests requirements data altering.
   */
  #[IgnoreDeprecations]
  public function testRequirementsAlter(): void {
    $requirements = $this->container->get('system.manager')->listRequirements();
    // @see requirements1_test_requirements_alter()
    $this->assertEquals('Requirements 1 Test - Changed', $requirements['requirements1_test_alterable']['title']);
    $this->assertEquals(RequirementSeverity::Warning, $requirements['requirements1_test_alterable']['severity']);
    $this->assertArrayNotHasKey('requirements1_test_deletable', $requirements);
    $this->expectDeprecation('requirements1_test_requirements without a #[LegacyRequirementsHook] attribute is deprecated in drupal:11.3.0 and removed in drupal:13.0.0. See https://www.drupal.org/node/3549685');
  }

}
