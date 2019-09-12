<?php

namespace Drupal\Tests\simpletest\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Test\EnvironmentCleanerInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Verify deprecation errors for the cleanup functions.
 *
 * @group simpletest
 * @group legacy
 */
class DeprecatedCleanupTest extends KernelTestBase {

  public static $modules = ['simpletest'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $cleaner_definition = new Definition(StubEnvironmentCleanerService::class);
    $container->setDefinition('environment_cleaner', $cleaner_definition);
  }

  /**
   * @expectedDeprecation simpletest_clean_environment is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Access the environment_cleaner service and call its cleanEnvironment() method, or use \Drupal\Core\Test\EnvironmentCleaner::cleanEnvironment() instead.. See https://www.drupal.org/node/3076634
   * @expectedDeprecation simpletest_clean_database is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Access the environment_cleaner service and call its cleanDatabase() method, or use \Drupal\Core\Test\EnvironmentCleaner::cleanDatabase() instead. See https://www.drupal.org/node/3076634
   * @expectedDeprecation simpletest_clean_temporary_directories is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Access the environment_cleaner service and call its cleanTemporaryDirectories() method, or use \Drupal\Core\Test\EnvironmentCleaner::cleanTemporaryDirectories() instead. See https://www.drupal.org/node/3076634
   * @expectedDeprecation simpletest_clean_results_table is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Access the environment_cleaner service and call its cleanResultsTable() method, or use \Drupal\Core\Test\EnvironmentCleaner::cleanResultsTable() instead. See https://www.drupal.org/node/3076634
   */
  public function testDeprecatedCleanFunctions() {
    $this->assertNull(simpletest_clean_environment());
    $this->assertNull(simpletest_clean_database());
    $this->assertNull(simpletest_clean_temporary_directories());
    $this->assertEquals(0, simpletest_clean_results_table());
  }

}

/**
 * Mock environment_cleaner service that does not perform any cleaning.
 */
class StubEnvironmentCleanerService implements EnvironmentCleanerInterface {

  public function cleanDatabase() {

  }

  public function cleanEnvironment($clear_results = TRUE, $clear_temp_directories = TRUE, $clear_database = TRUE) {

  }

  public function cleanResultsTable($test_id = NULL) {

  }

  public function cleanTemporaryDirectories() {

  }

}
