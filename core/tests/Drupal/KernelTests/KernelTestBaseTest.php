<?php

namespace Drupal\KernelTests;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Database\Database;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;

/**
 * @coversDefaultClass \Drupal\KernelTests\KernelTestBase
 *
 * @group PHPUnit
 * @group Test
 * @group KernelTests
 */
class KernelTestBaseTest extends KernelTestBase {

  /**
   * @covers ::setUpBeforeClass
   */
  public function testSetUpBeforeClass() {
    // Note: PHPUnit automatically restores the original working directory.
    $this->assertSame(realpath(__DIR__ . '/../../../../'), getcwd());
  }

  /**
   * @covers ::bootEnvironment
   */
  public function testBootEnvironment() {
    $this->assertRegExp('/^test\d{8}$/', $this->databasePrefix);
    $this->assertStringStartsWith('vfs://root/sites/simpletest/', $this->siteDirectory);
    $this->assertEquals([
      'root' => [
        'sites' => [
          'simpletest' => [
            substr($this->databasePrefix, 4) => [
              'files' => [
                'config' => [
                  'sync' => [],
                ],
              ],
            ],
          ],
        ],
      ],
    ], vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure());
  }

  /**
   * @covers ::getDatabaseConnectionInfo
   */
  public function testGetDatabaseConnectionInfoWithOutManualSetDbUrl() {
    $options = $this->container->get('database')->getConnectionOptions();
    $this->assertSame($this->databasePrefix, $options['prefix']['default']);
  }

  /**
   * @covers ::setUp
   */
  public function testSetUp() {
    $this->assertTrue($this->container->has('request_stack'));
    $this->assertTrue($this->container->initialized('request_stack'));
    $request = $this->container->get('request_stack')->getCurrentRequest();
    $this->assertNotEmpty($request);
    $this->assertEquals('/', $request->getPathInfo());

    $this->assertSame($request, \Drupal::request());

    $this->assertEquals($this, $GLOBALS['conf']['container_service_providers']['test']);

    $GLOBALS['destroy-me'] = TRUE;
    $this->assertArrayHasKey('destroy-me', $GLOBALS);

    $database = $this->container->get('database');
    $database->schema()->createTable('foo', [
      'fields' => [
        'number' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
      ],
    ]);
    $this->assertTrue($database->schema()->tableExists('foo'));

    // Ensure that the database tasks have been run during set up. Neither MySQL
    // nor SQLite make changes that are testable.
    if ($database->driver() == 'pgsql') {
      $this->assertEquals('on', $database->query("SHOW standard_conforming_strings")->fetchField());
      $this->assertEquals('escape', $database->query("SHOW bytea_output")->fetchField());
    }

    $this->assertNotNull(FileCacheFactory::getPrefix());
  }

  /**
   * @covers ::setUp
   * @depends testSetUp
   */
  public function testSetUpDoesNotLeak() {
    $this->assertArrayNotHasKey('destroy-me', $GLOBALS);

    // Ensure that we have a different database prefix.
    $schema = $this->container->get('database')->schema();
    $this->assertFalse($schema->tableExists('foo'));
  }

  /**
   * @covers ::register
   */
  public function testRegister() {
    // Verify that this container is identical to the actual container.
    $this->assertInstanceOf('Symfony\Component\DependencyInjection\ContainerInterface', $this->container);
    $this->assertSame($this->container, \Drupal::getContainer());

    // The request service should never exist.
    $this->assertFalse($this->container->has('request'));

    // Verify that there is a request stack.
    $request = $this->container->get('request_stack')->getCurrentRequest();
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\Request', $request);
    $this->assertSame($request, \Drupal::request());

    // Trigger a container rebuild.
    $this->enableModules(['system']);

    // Verify that this container is identical to the actual container.
    $this->assertInstanceOf('Symfony\Component\DependencyInjection\ContainerInterface', $this->container);
    $this->assertSame($this->container, \Drupal::getContainer());

    // The request service should never exist.
    $this->assertFalse($this->container->has('request'));

    // Verify that there is a request stack (and that it persisted).
    $new_request = $this->container->get('request_stack')->getCurrentRequest();
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\Request', $new_request);
    $this->assertSame($new_request, \Drupal::request());
    $this->assertSame($request, $new_request);

    // Ensure getting the router.route_provider does not trigger a deprecation
    // message that errors.
    $this->container->get('router.route_provider');
  }

  /**
   * Tests whether the fixture allows us to install modules and configuration.
   *
   * @see ::testSubsequentContainerIsolation()
   */
  public function testContainerIsolation() {
    $this->enableModules(['system', 'user']);
    $this->assertNull($this->installConfig('user'));
  }

  /**
   * Tests whether the fixture can re-install modules and configuration.
   *
   * @depends testContainerIsolation
   */
  public function testSubsequentContainerIsolation() {
    $this->enableModules(['system', 'user']);
    $this->assertNull($this->installConfig('user'));
  }

  /**
   * @covers ::render
   */
  public function testRender() {
    $type = 'processed_text';
    $element_info = $this->container->get('element_info');
    $this->assertSame(['#defaults_loaded' => TRUE], $element_info->getInfo($type));

    $this->enableModules(['filter']);

    $this->assertNotSame($element_info, $this->container->get('element_info'));
    $this->assertNotEmpty($this->container->get('element_info')->getInfo($type));

    $build = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Inner',
    ];
    $expected = "<h3>Inner</h3>\n";

    $this->assertEquals('core', \Drupal::theme()->getActiveTheme()->getName());
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->assertEquals('core', \Drupal::theme()->getActiveTheme()->getName());

    $this->assertEquals($expected, $build['#markup']);
    $this->assertEquals($expected, $output);
  }

  /**
   * @covers ::render
   */
  public function testRenderWithTheme() {
    $this->enableModules(['system']);

    $build = [
      '#type' => 'textfield',
      '#name' => 'test',
    ];
    $expected = '/' . preg_quote('<input type="text" name="test"', '/') . '/';

    $this->assertArrayNotHasKey('theme', $GLOBALS);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->assertEquals('core', \Drupal::theme()->getActiveTheme()->getName());

    $this->assertRegExp($expected, (string) $build['#children']);
    $this->assertRegExp($expected, (string) $output);
  }

  /**
   * @covers ::bootKernel
   */
  public function testFileDefaultScheme() {
    $this->assertEquals('public', file_default_scheme());
    $this->assertEquals('public', \Drupal::config('system.file')->get('default_scheme'));
  }

  /**
   * Tests the assumption that local time is in 'Australia/Sydney'.
   */
  public function testLocalTimeZone() {
    // The 'Australia/Sydney' time zone is set in core/tests/bootstrap.php
    $this->assertEquals('Australia/Sydney', date_default_timezone_get());
  }

  /**
   * Tests that a test method is skipped when it requires a module not present.
   *
   * In order to catch checkRequirements() regressions, we have to make a new
   * test object and run checkRequirements() here.
   *
   * @covers ::checkRequirements
   * @covers ::checkModuleRequirements
   */
  public function testMethodRequiresModule() {
    require __DIR__ . '/../../fixtures/KernelMissingDependentModuleMethodTest.php';

    $stub_test = new KernelMissingDependentModuleMethodTest();
    // We have to setName() to the method name we're concerned with.
    $stub_test->setName('testRequiresModule');

    // We cannot use $this->setExpectedException() because PHPUnit would skip
    // the test before comparing the exception type.
    try {
      $stub_test->publicCheckRequirements();
      $this->fail('Missing required module throws skipped test exception.');
    }
    catch (\PHPUnit_Framework_SkippedTestError $e) {
      $this->assertEqual('Required modules: module_does_not_exist', $e->getMessage());
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
  public function testRequiresModule() {
    require __DIR__ . '/../../fixtures/KernelMissingDependentModuleTest.php';

    $stub_test = new KernelMissingDependentModuleTest();
    // We have to setName() to the method name we're concerned with.
    $stub_test->setName('testRequiresModule');

    // We cannot use $this->setExpectedException() because PHPUnit would skip
    // the test before comparing the exception type.
    try {
      $stub_test->publicCheckRequirements();
      $this->fail('Missing required module throws skipped test exception.');
    }
    catch (\PHPUnit_Framework_SkippedTestError $e) {
      $this->assertEqual('Required modules: module_does_not_exist', $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();

    // Check that all tables of the test instance have been deleted. At this
    // point the original database connection is restored so we need to prefix
    // the tables.
    $connection = Database::getConnection();
    if ($connection->databaseType() != 'sqlite') {
      $tables = $connection->schema()->findTables($this->databasePrefix . '%');
      $this->assertTrue(empty($tables), 'All test tables have been removed.');
    }
    else {
      $result = $connection->query("SELECT name FROM " . $this->databasePrefix . ".sqlite_master WHERE type = :type AND name LIKE :table_name AND name NOT LIKE :pattern", [
        ':type' => 'table',
        ':table_name' => '%',
        ':pattern' => 'sqlite_%',
      ])->fetchAllKeyed(0, 0);

      $this->assertTrue(empty($result), 'All test tables have been removed.');
    }
  }

}
