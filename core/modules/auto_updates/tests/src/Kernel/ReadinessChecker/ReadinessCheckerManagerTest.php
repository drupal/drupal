<?php

namespace Drupal\Tests\auto_updates\Kernel\ReadinessChecker;

use Drupal\auto_updates_test\ReadinessChecker\TestChecker1;
use Drupal\auto_updates_test2\ReadinessChecker\TestChecker2;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\auto_updates\Traits\ReadinessCheckerTestTrait;

/**
 * @coversDefaultClass \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager
 *
 * @group auto_updates
 */
class ReadinessCheckerManagerTest extends KernelTestBase {

  use ReadinessCheckerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['auto_updates_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createTestReadinessCheckerResults();
  }

  /**
   * @covers ::getResults
   */
  public function testGetResults(): void {
    $this->enableModules(['auto_updates', 'auto_updates_test2']);
    $this->installConfig(['auto_updates']);
    $this->assertSame([], $this->getResultsFromManager(TRUE));
    $expected_results = [
      array_pop($this->testResults['checker_1']),
      array_pop($this->testResults['checker_2']),
    ];
    TestChecker1::setTestResult($expected_results[0]);
    TestChecker2::setTestResult($expected_results[1]);
    $this->assertCheckerResultsEqual($expected_results, TRUE);

    // Define a constant flag that will cause the readiness checker
    // service priority to be altered.
    // @see \Drupal\auto_updates_test\AutoUpdatesTestServiceProvider::alter().
    define('AUTO_UPDATES_TEST_SET_PRIORITY', 5);
    // Rebuild the container to trigger the service to be altered.
    $kernel = $this->container->get('kernel');
    $this->container = $kernel->rebuildContainer();
    // Confirm that results will be NULL if the run() is not called again
    // because the readiness checker services order has been altered.
    $this->assertNull($this->getResultsFromManager());
    // Confirm that after calling run() the expected results order has changed.
    $expected_results = array_reverse($expected_results);
    $this->assertCheckerResultsEqual($expected_results, TRUE);
  }

  /**
   * Tests that the manager is run after modules are installed.
   */
  public function testRunOnInstall(): void {
    $expected_results = [array_pop($this->testResults['checker_1'])];
    TestChecker1::setTestResult($expected_results[0]);
    // Confirm that messages from an existing module are displayed when
    // 'auto_updates' is installed.
    $this->container->get('module_installer')->install(['auto_updates']);
    $this->assertCheckerResultsEqual($expected_results);

    // Confirm that the checkers are run when a module that provides a readiness
    // checker is installed.
    $expected_results = [
      array_pop($this->testResults['checker_1']),
      array_pop($this->testResults['checker_2']),
    ];
    TestChecker1::setTestResult($expected_results[0]);
    TestChecker2::setTestResult($expected_results[1]);
    $this->container->get('module_installer')->install(['auto_updates_test2']);
    $this->assertCheckerResultsEqual($expected_results);

    // Confirm that the checkers are not run when a module that does not provide
    // a readiness checker is installed.
    $unexpected_results = [
      array_pop($this->testResults['checker_1']),
      array_pop($this->testResults['checker_2']),
    ];
    TestChecker1::setTestResult($unexpected_results[0]);
    TestChecker2::setTestResult($unexpected_results[1]);
    $this->container->get('module_installer')->install(['help']);
    $this->assertCheckerResultsEqual($expected_results);
  }

  /**
   * Tests that the manager is run after modules are uninstalled.
   */
  public function testRunOnUninstall(): void {
    $expected_results = [
      array_pop($this->testResults['checker_1']),
      array_pop($this->testResults['checker_2']),
    ];
    TestChecker1::setTestResult($expected_results[0]);
    TestChecker2::setTestResult($expected_results[1]);
    // Confirm that messages from existing modules are displayed when
    // 'auto_updates' is installed.
    $this->container->get('module_installer')->install(['auto_updates', 'auto_updates_test2', 'help']);
    $this->assertCheckerResultsEqual($expected_results);

    // Confirm that the checkers are run when a module that provides a readiness
    // checker is uninstalled.
    $expected_results = [
      array_pop($this->testResults['checker_1']),
    ];
    TestChecker1::setTestResult($expected_results[0]);
    TestChecker2::setTestResult(array_pop($this->testResults['checker_2']));
    $this->container->get('module_installer')->uninstall(['auto_updates_test2']);
    $this->assertCheckerResultsEqual($expected_results);

    // Confirm that the checkers are not run when a module that does provide a
    // readiness checker is uninstalled.
    $unexpected_results = [
      array_pop($this->testResults['checker_1']),
    ];
    TestChecker1::setTestResult($unexpected_results[0]);
    $this->container->get('module_installer')->uninstall(['help']);
    $this->assertCheckerResultsEqual($expected_results);
  }

  /**
   * @covers ::runIfNeeded
   */
  public function testRunIfNeeded(): void {
    $expected_results = [
      array_pop($this->testResults['checker_1']),
    ];
    TestChecker1::setTestResult($expected_results[0]);
    $this->container->get('module_installer')->install(['auto_updates']);
    $this->assertCheckerResultsEqual($expected_results);

    $unexpected_results = [
      array_pop($this->testResults['checker_1']),
    ];
    TestChecker1::setTestResult($unexpected_results[0]);
    $manager = $this->container->get('auto_updates.readiness_checker_manager');
    // Confirm that the new results will not be returned because the checkers
    // will not be run.
    $manager->runIfNeeded();
    $this->assertCheckerResultsEqual($expected_results);

    // Confirm that the new results will be returned because the checkers will
    // be run if the stored results are deleted.
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value */
    $key_value = $this->container->get('keyvalue.expirable')->get('auto_updates');
    $key_value->delete('readiness_check_last_run');
    $expected_results = $unexpected_results;
    $manager->runIfNeeded();
    $this->assertCheckerResultsEqual($expected_results);

    // Confirm that the results are the same after rebuilding the container.
    $unexpected_results = [
      array_pop($this->testResults['checker_1']),
    ];
    TestChecker1::setTestResult($unexpected_results[0]);
    /** @var \Drush\Drupal\DrupalKernel $kernel */
    $kernel = $this->container->get('kernel');
    $this->container = $kernel->rebuildContainer();
    $this->assertCheckerResultsEqual($expected_results);

    // Define a constant flag that will cause a duplicate readiness checker
    // service to be defined.
    // @see \Drupal\auto_updates_test\AutoUpdatesTestServiceProvider::alter().
    define('AUTO_UPDATES_TEST_DUPLICATE_SERVICE', TRUE);

    // Rebuild the container to trigger the service to be duplicated.
    $kernel = $this->container->get('kernel');
    $this->container = $kernel->rebuildContainer();
    $expected_results = $unexpected_results;
    $manager = $this->container->get('auto_updates.readiness_checker_manager');
    $manager->runIfNeeded();
    $this->assertCheckerResultsEqual($expected_results);
  }

  /**
   * Gets the messages of a particular type from the manager.
   *
   * @param bool $call_run
   *   Whether to run the checkers.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[]|null
   *   The messages of the type.
   */
  protected function getResultsFromManager(bool $call_run = FALSE): ?array {
    $manager = $this->container->get('auto_updates.readiness_checker_manager');
    if ($call_run) {
      $manager->run();
    }
    return $manager->getResults();
  }

  /**
   * Asserts expected readiness checker results from the manager.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[] $expected_results
   *   The expected results.
   * @param bool $call_run
   *   (Optional) Whether to call ::run() on the manager. Defaults to FALSE.
   */
  private function assertCheckerResultsEqual(array $expected_results, bool $call_run = FALSE): void {
    $actual_results = $this->getResultsFromManager($call_run);
    $this->assertCount(count($expected_results), $actual_results);

    foreach ($expected_results as $expected_result) {
      $actual_result = array_shift($actual_results);
      $this->assertSame((string) $expected_result->getErrorsSummary(), (string) $actual_result->getErrorsSummary());
      $this->assertSame((string) $expected_result->getWarningsSummary(), (string) $actual_result->getWarningsSummary());
      $this->assertSame(
        array_map('strval', $expected_result->getErrorMessages()),
        array_map('strval', $actual_result->getErrorMessages())
      );
      $this->assertSame(
        array_map('strval', $expected_result->getWarningMessages()),
        array_map('strval', $actual_result->getWarningMessages())
      );
    }
  }

}
