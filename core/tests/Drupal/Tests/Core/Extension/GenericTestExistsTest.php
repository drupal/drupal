<?php

namespace Drupal\Tests\Core\Extension;

use Drupal\Tests\UnitTestCase;
use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;

/**
 * Tests that the Generic module test exists for all modules.
 *
 * @group Extension
 */
class GenericTestExistsTest extends UnitTestCase {

  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * Lists module that do not require a Generic test.
   */
  protected $modulesNoTest = ['help_topics'];

  /**
   * Tests that the Generic module test exists for all modules.
   *
   * @dataProvider coreModuleListDataProvider
   */
  public function testGenericTestExists(string $module_name): void {
    if (in_array($module_name, $this->modulesNoTest, TRUE)) {
      $this->markTestSkipped();
    }
    $this->assertFileExists("{$this->root}/core/modules/{$module_name}/tests/src/Functional/GenericTest.php");
  }

}
