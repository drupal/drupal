<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Database\Database;
use Drupal\Core\Extension\DatabaseDriverList;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Core\Database\Database
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @group Database
 */
class DatabaseTest extends UnitTestCase {

  /**
   * A classloader to enable testing of contrib drivers.
   *
   * @var \Composer\Autoload\ClassLoader
   */
  protected $additionalClassloader;

  /**
   * Path to DRUPAL_ROOT.
   *
   * @var string
   */
  protected $root;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->additionalClassloader = new ClassLoader();
    $this->additionalClassloader->register();
    // Mock the container so we don't need to mock drupal_valid_test_ua().
    // @see \Drupal\Core\Extension\ExtensionDiscovery::scan()
    $this->root = dirname(__DIR__, 6);
    $databaseDriverList = new DatabaseDriverList($this->root, 'database_driver', new NullBackend('database_driver'));
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->any())
      ->method('has')
      ->willReturnMap([
        ['kernel', TRUE],
        ['extension.list.database_driver', TRUE],
      ]);
    $container->expects($this->any())
      ->method('get')
      ->with('extension.list.database_driver')
      ->willReturn($databaseDriverList);
    $container->expects($this->any())
      ->method('getParameter')
      ->with('site.path')
      ->willReturn('');
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::findDriverAutoloadDirectory
   * @dataProvider providerFindDriverAutoloadDirectory
   * @group legacy
   */
  public function testFindDriverAutoloadDirectory($expected, $namespace, $include_test_drivers) {
    $this->expectDeprecation('Drupal\Core\Database\Database::findDriverAutoloadDirectory() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use DatabaseDriverList::getList() instead. See https://www.drupal.org/node/3258175');
    // The only module that provides a driver in core is a test module.
    if (!$expected) {
      $this->expectException(UnknownExtensionException::class);
      Database::findDriverAutoloadDirectory($namespace, $this->root, $include_test_drivers);
    }
    else {
      $this->assertSame($expected, Database::findDriverAutoloadDirectory($namespace, $this->root, $include_test_drivers));
    }
  }

  /**
   * Data provider for ::testFindDriverAutoloadDirectory().
   *
   * @return array
   */
  public function providerFindDriverAutoloadDirectory() {
    return [
      'core mysql' => ['core/modules/mysql/src/Driver/Database/mysql/', 'Drupal\mysql\Driver\Database\mysql', FALSE],
      'D8 custom fake' => [FALSE, 'Drupal\Driver\Database\CoreFake', TRUE],
      'module mysql' => ['core/modules/system/tests/modules/driver_test/src/Driver/Database/DrivertestMysql/', 'Drupal\driver_test\Driver\Database\DrivertestMysql', TRUE],
    ];
  }

  /**
   * @covers ::findDriverAutoloadDirectory
   * @dataProvider providerFindDriverAutoloadDirectoryException
   * @group legacy
   */
  public function testFindDriverAutoloadDirectoryException($expected_message, $namespace, $include_tests) {
    $this->expectDeprecation('Drupal\Core\Database\Database::findDriverAutoloadDirectory() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use DatabaseDriverList::getList() instead. See https://www.drupal.org/node/3258175');
    $this->expectException(UnknownExtensionException::class);
    $this->expectExceptionMessage($expected_message);
    Database::findDriverAutoloadDirectory($namespace, $this->root, $include_tests);
  }

  /**
   * Data provider for ::testFindDriverAutoloadDirectoryException().
   *
   * @return array
   */
  public function providerFindDriverAutoloadDirectoryException() {
    return [
      'test module but tests not included' => [
        "The database_driver Drupal\driver_test\Driver\Database\DrivertestMysql does not exist.",
        'Drupal\driver_test\Driver\Database\DrivertestMysql',
        FALSE,
      ],
      'non-existent driver in test module' => [
        "The database_driver Drupal\driver_test\Driver\Database\sqlite does not exist.",
        'Drupal\driver_test\Driver\Database\sqlite',
        TRUE,
      ],
      'non-existent module' => [
        "The database_driver Drupal\does_not_exist\Driver\Database\mysql does not exist.",
        'Drupal\does_not_exist\Driver\Database\mysql',
        TRUE,
      ],
    ];
  }

  /**
   * Adds a database driver that uses the D8's Drupal\Driver\Database namespace.
   */
  protected function addD8CustomDrivers() {
    $this->additionalClassloader->addPsr4("Drupal\\Driver\\Database\\CoreFake\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/custom/CoreFake");
  }

  /**
   * Adds database drivers that are provided by modules.
   */
  protected function addModuleDrivers() {
    $this->additionalClassloader->addPsr4("Drupal\\driver_test\\Driver\\Database\\DrivertestMysql\\", __DIR__ . "/../../../../../modules/system/tests/modules/driver_test/src/Driver/Database/DrivertestMysql");
    $this->additionalClassloader->addPsr4("Drupal\\CoreFake\\Driver\\Database\\CoreFake\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/module/core_fake/src/Driver/Database/CoreFake");
  }

}
