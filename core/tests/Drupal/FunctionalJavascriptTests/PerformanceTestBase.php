<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests;

use Drupal\Core\Database\Database;
use Drupal\Tests\PerformanceTestTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Collects performance metrics.
 *
 * @ingroup testing
 */
class PerformanceTestBase extends WebDriverTestBase {
  use PerformanceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['performance_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->doSetUpTasks();
    \Drupal::service('module_installer')->uninstall(['automated_cron']);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    $db = Database::getConnection();
    $test_file_name = (new \ReflectionClass($this))->getFileName();
    $is_core_test = str_starts_with($test_file_name, DRUPAL_ROOT . DIRECTORY_SEPARATOR . 'core');
    if ($db->databaseType() !== 'mysql' && $is_core_test) {
      $this->markTestSkipped('Drupal core performance tests only run on MySQL');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function installModulesFromClassProperty(ContainerInterface $container) {
    $this->doInstallModulesFromClassProperty($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function getMinkDriverArgs(): string {
    return $this->doGetMinkDriverArgs();
  }

}
