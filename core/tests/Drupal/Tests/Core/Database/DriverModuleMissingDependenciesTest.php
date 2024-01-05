<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Extension\DatabaseDriverList;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests for database driver module with missing dependency.
 *
 * These tests run in isolation since we don't want the database static to
 * affect other tests. We also use a fake root directory to avoid the failing
 * module to get into normal extensions discovery.
 *
 * @coversDefaultClass \Drupal\Core\Extension\DatabaseDriverList
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @group Database
 */
class DriverModuleMissingDependenciesTest extends UnitTestCase {

  /**
   * @covers ::get
   */
  public function testDetermineDriversAutoloadingFailingOnMissingDependency(): void {
    $root = realpath(dirname(__FILE__) . '/fixtures');

    // Mock the container so we don't need to mock drupal_valid_test_ua().
    // @see \Drupal\Core\Extension\ExtensionDiscovery::scan()
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->any())
      ->method('has')
      ->with('kernel')
      ->willReturn(TRUE);
    $container->expects($this->any())
      ->method('getParameter')
      ->with()
      ->willReturnMap([
          ['install_profile', ''],
          ['site.path', ''],
      ]);
    $container->expects($this->any())
      ->method('get')
      ->with('extension.list.database_driver')
      ->willReturn(new DatabaseDriverList($root, 'database_driver', new NullBackend('database_driver')));
    \Drupal::setContainer($container);

    $this->expectException(UnknownExtensionException::class);
    $this->expectExceptionMessage("The database_driver a_really_missing_module\dependent_driver does not exist.");
    $container->get('extension.list.database_driver')
      ->includeTestDrivers(TRUE)
      ->get('a_really_missing_module\\dependent_driver')
      ->getAutoloadInfo();
  }

}
