<?php

namespace Drupal\Tests\Core\Database;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Database\Database;
use Drupal\Core\Site\Settings;
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
  protected function setUp() {
    $this->additionalClassloader = new ClassLoader();
    $this->additionalClassloader->register();
    // Mock the container so we don't need to mock drupal_valid_test_ua().
    // @see \Drupal\Core\Extension\ExtensionDiscovery::scan()
    $this->root = dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))));
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->any())
      ->method('has')
      ->with('kernel')
      ->willReturn(TRUE);
    $container->expects($this->any())
      ->method('get')
      ->with('site.path')
      ->willReturn('');
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::findDriverAutoloadDirectory
   * @dataProvider providerFindDriverAutoloadDirectory
   */
  public function testFindDriverAutoloadDirectory($expected, $namespace) {
    new Settings(['extension_discovery_scan_tests' => TRUE]);
    // The only module that provides a driver in core is a test module.
    $this->assertSame($expected, Database::findDriverAutoloadDirectory($namespace, $this->root));
  }

  /**
   * Data provider for ::testFindDriverAutoloadDirectory().
   *
   * @return array
   */
  public function providerFindDriverAutoloadDirectory() {
    return [
      'core mysql' => [FALSE, 'Drupal\Core\Database\Driver\mysql'],
      'D8 custom fake' => [FALSE, 'Drupal\Driver\Database\corefake'],
      'module mysql' => ['core/modules/system/tests/modules/driver_test/src/Driver/Database/DrivertestMysql/', 'Drupal\driver_test\Driver\Database\DrivertestMysql'],
    ];
  }

  /**
   * @covers ::findDriverAutoloadDirectory
   * @dataProvider providerFindDriverAutoloadDirectoryException
   */
  public function testFindDriverAutoloadDirectoryException($expected_message, $namespace, $include_tests) {
    new Settings(['extension_discovery_scan_tests' => $include_tests]);
    if ($include_tests === FALSE) {
      // \Drupal\Core\Extension\ExtensionDiscovery::scan() needs
      // drupal_valid_test_ua().
      include $this->root . '/core/includes/bootstrap.inc';
    }
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage($expected_message);
    Database::findDriverAutoloadDirectory($namespace, $this->root);
  }

  /**
   * Data provider for ::testFindDriverAutoloadDirectoryException().
   *
   * @return array
   */
  public function providerFindDriverAutoloadDirectoryException() {
    return [
      'test module but tests not included' => ["Cannot find the module 'driver_test' for the database driver namespace 'Drupal\driver_test\Driver\Database\DrivertestMysql'", 'Drupal\driver_test\Driver\Database\DrivertestMysql', FALSE],
      'non-existent driver in test module' => ["Cannot find the database driver namespace 'Drupal\driver_test\Driver\Database\sqlite' in module 'driver_test'", 'Drupal\driver_test\Driver\Database\sqlite', TRUE],
      'non-existent module' => ["Cannot find the module 'does_not_exist' for the database driver namespace 'Drupal\does_not_exist\Driver\Database\mysql'", 'Drupal\does_not_exist\Driver\Database\mysql', TRUE],
    ];
  }

  /**
   * Adds a database driver that uses the D8's Drupal\Driver\Database namespace.
   */
  protected function addD8CustomDrivers() {
    $this->additionalClassloader->addPsr4("Drupal\\Driver\\Database\\corefake\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/custom/corefake");
  }

  /**
   * Adds database drivers that are provided by modules.
   */
  protected function addModuleDrivers() {
    $this->additionalClassloader->addPsr4("Drupal\\driver_test\\Driver\\Database\\DrivertestMysql\\", __DIR__ . "/../../../../../modules/system/tests/modules/driver_test/src/Driver/Database/DrivertestMysql");
    $this->additionalClassloader->addPsr4("Drupal\\corefake\\Driver\\Database\\corefake\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/module/corefake/src/Driver/Database/corefake");
  }

}
