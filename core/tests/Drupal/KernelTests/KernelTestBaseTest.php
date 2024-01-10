<?php

namespace Drupal\KernelTests;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Utility\Random;
use Drupal\Core\Database\Database;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Tests\StreamCapturer;
use Drupal\user\Entity\Role;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use PHPUnit\Framework\SkippedTestError;

/**
 * @coversDefaultClass \Drupal\KernelTests\KernelTestBase
 *
 * @group PHPUnit
 * @group Test
 * @group KernelTests
 * @group #slow
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
    $this->assertMatchesRegularExpression('/^test\d{8}$/', $this->databasePrefix);
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
    $this->assertSame($this->databasePrefix, $options['prefix']);
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
   * Tests that an outbound HTTP request can be performed inside of a test.
   */
  public function testOutboundHttpRequest() {
    // The middleware test.http_client.middleware calls drupal_generate_test_ua
    // which checks the DRUPAL_TEST_IN_CHILD_SITE constant, that is not defined
    // in Kernel tests.
    try {
      /** @var \GuzzleHttp\Psr7\Response $response */
      $response = $this->container->get('http_client')->head('http://example.com');
      self::assertEquals(200, $response->getStatusCode());
    }
    catch (\Throwable $e) {
      // Ignore any HTTP errors, any other exception is considered an error.
      self::assertInstanceOf(GuzzleException::class, $e, sprintf('Asserting that a possible exception is thrown. Got "%s" with message: "%s".', get_class($e), $e->getMessage()));
    }
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

    $this->assertSame($expected, (string) $build['#markup']);
    $this->assertSame($expected, (string) $output);
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

    $this->assertMatchesRegularExpression($expected, (string) $build['#children']);
    $this->assertMatchesRegularExpression($expected, (string) $output);
  }

  /**
   * @covers ::bootKernel
   */
  public function testBootKernel() {
    $this->assertNull($this->container->get('request_stack')->getParentRequest(), 'There should only be one request on the stack');
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

    // @phpstan-ignore-next-line
    $stub_test = new KernelMissingDependentModuleMethodTest();
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
  public function testRequiresModule() {
    require __DIR__ . '/../../fixtures/KernelMissingDependentModuleTest.php';

    // @phpstan-ignore-next-line
    $stub_test = new KernelMissingDependentModuleTest();
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
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();

    // Check that all tables of the test instance have been deleted. At this
    // point the original database connection is restored so we need to prefix
    // the tables.
    $connection = Database::getConnection();
    if ($connection->databaseType() === 'sqlite') {
      $result = $connection->query("SELECT name FROM " . $this->databasePrefix .
        ".sqlite_master WHERE type = :type AND name LIKE :table_name AND name NOT LIKE :pattern", [
          ':type' => 'table',
          ':table_name' => '%',
          ':pattern' => 'sqlite_%',
        ]
      )->fetchAllKeyed(0, 0);
      $this->assertEmpty($result, 'All test tables have been removed.');
    }
    else {
      $tables = $connection->schema()->findTables($this->databasePrefix . '%');
      $this->assertEmpty($tables, 'All test tables have been removed.');
    }
  }

  /**
   * Ensures KernelTestBase tests can access modules in profiles.
   */
  public function testProfileModules() {
    $this->assertFileExists('core/profiles/demo_umami/modules/demo_umami_content/demo_umami_content.info.yml');
    $this->assertSame(
      'core/profiles/demo_umami/modules/demo_umami_content/demo_umami_content.info.yml',
      \Drupal::service('extension.list.module')->getPathname('demo_umami_content')
    );
  }

  /**
   * Tests the dump() function provided by the var-dumper Symfony component.
   */
  public function testVarDump() {
    // Append the stream capturer to the STDOUT stream, so that we can test the
    // dump() output and also prevent it from actually outputting in this
    // particular test.
    stream_filter_register("capture", StreamCapturer::class);
    stream_filter_append(STDOUT, "capture");

    // Dump some variables.
    $this->enableModules(['system', 'user']);
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role']);
    dump($role);
    dump($role->id());

    $this->assertStringContainsString('Drupal\user\Entity\Role', StreamCapturer::$cache);
    $this->assertStringContainsString('test_role', StreamCapturer::$cache);
  }

  /**
   * @covers ::bootEnvironment
   */
  public function testDatabaseDriverModuleEnabled() {
    $module = Database::getConnection()->getProvider();

    // Test that the module that is providing the database driver is enabled.
    $this->assertSame(1, \Drupal::service('extension.list.module')->get($module)->status);
  }

  /**
   * Tests the deprecation of accessing the randomGenerator property directly.
   *
   * @group legacy
   */
  public function testGetRandomGeneratorPropertyDeprecation() {
    $this->expectDeprecation('Accessing the randomGenerator property is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use getRandomGenerator() instead. See https://www.drupal.org/node/3358445');
    // We purposely test accessing an undefined property here. We need to tell
    // PHPStan to ignore that.
    // @phpstan-ignore-next-line
    $this->assertInstanceOf(Random::class, $this->randomGenerator);
  }

}
