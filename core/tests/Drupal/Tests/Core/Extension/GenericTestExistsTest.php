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
  protected $modulesNoTest = [
    'help_topics',
    'sdc',
    'migrate_drupal',
    'migrate_drupal_ui',
  ];

  /**
   * Tests that the Generic module test exists for all modules.
   */
  #[DataProvider('coreModuleListDataProvider')]
  public function testGenericTestExists(string $module_name): void {
    if (in_array($module_name, $this->modulesNoTest, TRUE)) {
      $this->markTestSkipped();
    }

    // @todo Revert to the previous version of this assertion when all core
    // modules have their GenericTest converted to a Kernel test.
    $this->assertTrue(
      file_exists("{$this->root}/core/modules/{$module_name}/tests/src/Functional/GenericTest.php") ||
      file_exists("{$this->root}/core/modules/{$module_name}/tests/src/Kernel/GenericTest.php")
    );
  }

}
