<?php

namespace Drupal\Tests\system\Kernel\Module;

use Drupal\KernelTests\KernelTestBase;

/**
 * Covers hook_requirements and hook_requirements_alter.
 * @group Module
 */
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
  public function testRequirementsAlter(): void {
    $requirements = $this->container->get('system.manager')->listRequirements();
    // @see requirements1_test_requirements_alter()
    $this->assertEquals('Requirements 1 Test - Changed', $requirements['requirements1_test_alterable']['title']);
    $this->assertEquals(REQUIREMENT_WARNING, $requirements['requirements1_test_alterable']['severity']);
    $this->assertArrayNotHasKey('requirements1_test_deletable', $requirements);
  }

}
