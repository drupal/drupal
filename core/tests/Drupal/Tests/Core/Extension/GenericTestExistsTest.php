<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that the Generic module test exists for all modules.
 */
#[Group('Extension')]
class GenericTestExistsTest extends UnitTestCase {

  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * Lists module that do not require a Generic test.
   *
   * @var string[]
   */
  protected $modulesNoTest = ['help_topics', 'sdc'];

  /**
   * Tests that the Generic module test exists for all modules.
   */
  #[DataProvider('coreModuleListDataProvider')]
  public function testGenericTestExists(string $module_name): void {
    if (in_array($module_name, $this->modulesNoTest, TRUE)) {
      $this->markTestSkipped();
    }
    $this->assertFileExists("{$this->root}/core/modules/{$module_name}/tests/src/Functional/GenericTest.php");
  }

}
