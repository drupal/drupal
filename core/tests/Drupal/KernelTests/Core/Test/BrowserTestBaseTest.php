<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Test;

use Drupal\FunctionalTests\BrowserMissingDependentModuleMethodTest;
use Drupal\FunctionalTests\BrowserMissingDependentModuleTest;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\SkippedTestError;

/**
 * @group Test
 * @group FunctionalTests
 * @group legacy
 *
 * @coversDefaultClass \Drupal\Tests\BrowserTestBase
 */
class BrowserTestBaseTest extends KernelTestBase {

  /**
   * Tests that a test method is skipped when it requires a module not present.
   *
   * In order to catch checkRequirements() regressions, we have to make a new
   * test object and run checkRequirements() here.
   *
   * @covers ::checkRequirements
   * @covers ::checkModuleRequirements
   */
  public function testMethodRequiresModule(): void {
    $this->expectDeprecation('Drupal\Tests\TestRequirementsTrait::checkModuleRequirements() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3418480');
    require __DIR__ . '/../../../../fixtures/BrowserMissingDependentModuleMethodTest.php';

    // @phpstan-ignore-next-line
    $stub_test = new BrowserMissingDependentModuleMethodTest();
    // We have to setName() to the method name we're concerned with.
    $stub_test->setName('testRequiresModule');

    // We cannot use $this->setExpectedException() because PHPUnit would skip
    // the test before comparing the exception type.
    try {
      $stub_test->publicCheckRequirements();
      $this->fail('Missing required module throws skipped test exception.');
    }
    catch (SkippedTestError $e) {
      $this->assertEquals('Required modules: module_does_not_exist', $e->getMessage());
    }
  }

  /**
   * Tests that a test case is skipped when it requires a module not present.
   *
   * In order to catch checkRequirements() regressions, we have to make a new
   * test object and run checkRequirements() here.
   *
   * @covers ::checkRequirements
   * @covers ::checkModuleRequirements
   */
  public function testRequiresModule(): void {
    $this->expectDeprecation('Drupal\Tests\TestRequirementsTrait::checkModuleRequirements() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3418480');
    require __DIR__ . '/../../../../fixtures/BrowserMissingDependentModuleTest.php';

    // @phpstan-ignore-next-line
    $stub_test = new BrowserMissingDependentModuleTest();
    // We have to setName() to the method name we're concerned with.
    $stub_test->setName('testRequiresModule');

    // We cannot use $this->setExpectedException() because PHPUnit would skip
    // the test before comparing the exception type.
    try {
      $stub_test->publicCheckRequirements();
      $this->fail('Missing required module throws skipped test exception.');
    }
    catch (SkippedTestError $e) {
      $this->assertEquals('Required modules: module_does_not_exist', $e->getMessage());
    }
  }

}
