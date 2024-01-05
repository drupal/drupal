<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Extension\ModuleHandler
 * @runTestsInSeparateProcesses
 *
 * @group Extension
 */
class ModuleHandlerTest extends UnitTestCase {

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheBackend;

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp(): void {
    parent::setUp();
    // We can mock the cache handler here, but not the module handler.
    $this->cacheBackend = $this->createMock(CacheBackendInterface::class);
  }

  /**
   * Get a module handler object to test.
   *
   * Since we have to run these tests in separate processes, we have to use
   * test objects which are serializable. Since ModuleHandler will populate
   * itself with Extension objects, and since Extension objects will try to
   * access DRUPAL_ROOT when they're unserialized, we can't store our mocked
   * ModuleHandler objects as a property in unit tests. They must be generated
   * by the test method by calling this method.
   *
   * @return \Drupal\Core\Extension\ModuleHandler
   *   The module handler to test.
   */
  protected function getModuleHandler() {
    $module_handler = new ModuleHandler($this->root, [
      'module_handler_test' => [
        'type' => 'module',
        'pathname' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test/module_handler_test.info.yml',
        'filename' => 'module_handler_test.module',
      ],
    ], $this->cacheBackend);
    return $module_handler;
  }

  /**
   * Tests loading a module.
   *
   * @covers ::load
   */
  public function testLoadModule() {
    $module_handler = $this->getModuleHandler();
    $this->assertFalse(function_exists('module_handler_test_hook'));
    $this->assertTrue($module_handler->load('module_handler_test'));
    $this->assertTrue(function_exists('module_handler_test_hook'));

    $module_handler->addModule('module_handler_test_added', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_added');
    $this->assertFalse(function_exists('module_handler_test_added_hook'), 'Function does not exist before being loaded.');
    $this->assertTrue($module_handler->load('module_handler_test_added'));
    $this->assertTrue(function_exists('module_handler_test_added_helper'), 'Function exists after being loaded.');
    $this->assertTrue($module_handler->load('module_handler_test_added'));

    $this->assertFalse($module_handler->load('module_handler_test_dne'), 'Non-existent modules returns false.');
  }

  /**
   * Tests loading all modules.
   *
   * @covers ::loadAll
   */
  public function testLoadAllModules() {
    $module_handler = $this->getModuleHandler();
    $module_handler->addModule('module_handler_test_all1', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all1');
    $module_handler->addModule('module_handler_test_all2', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all2');
    $this->assertFalse(function_exists('module_handler_test_all1_hook'), 'Function does not exist before being loaded.');
    $this->assertFalse(function_exists('module_handler_test_all2_hook'), 'Function does not exist before being loaded.');
    $module_handler->loadAll();
    $this->assertTrue(function_exists('module_handler_test_all1_hook'), 'Function exists after being loaded.');
    $this->assertTrue(function_exists('module_handler_test_all2_hook'), 'Function exists after being loaded.');
  }

  /**
   * Tests reload method.
   *
   * @covers ::reload
   */
  public function testModuleReloading() {
    $module_handler = $this->getMockBuilder(ModuleHandler::class)
      ->setConstructorArgs([
        $this->root,
        [
          'module_handler_test' => [
            'type' => 'module',
            'pathname' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test/module_handler_test.info.yml',
            'filename' => 'module_handler_test.module',
          ],
        ], $this->cacheBackend,
      ])
      ->onlyMethods(['load'])
      ->getMock();
    $module_handler->expects($this->exactly(3))
      ->method('load')
      ->withConsecutive(
        // First reload.
        ['module_handler_test'],
        // Second reload.
        ['module_handler_test'],
        ['module_handler_test_added'],
      );
    $module_handler->reload();
    $module_handler->addModule('module_handler_test_added', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_added');
    $module_handler->reload();
  }

  /**
   * Tests isLoaded accessor.
   *
   * @covers ::isLoaded
   */
  public function testIsLoaded() {
    $module_handler = $this->getModuleHandler();
    $this->assertFalse($module_handler->isLoaded());
    $module_handler->loadAll();
    $this->assertTrue($module_handler->isLoaded());
  }

  /**
   * Confirm we get back the modules set in the constructor.
   *
   * @covers ::getModuleList
   */
  public function testGetModuleList() {
    $this->assertEquals($this->getModuleHandler()->getModuleList(), [
      'module_handler_test' => new Extension($this->root, 'module', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test/module_handler_test.info.yml', 'module_handler_test.module'),
    ]);
  }

  /**
   * Confirm we get back a module from the module list.
   *
   * @covers ::getModule
   */
  public function testGetModuleWithExistingModule() {
    $this->assertEquals($this->getModuleHandler()->getModule('module_handler_test'), new Extension($this->root, 'module', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test/module_handler_test.info.yml', 'module_handler_test.module'));
  }

  /**
   * @covers ::getModule
   */
  public function testGetModuleWithNonExistingModule() {
    $this->expectException(UnknownExtensionException::class);
    $this->getModuleHandler()->getModule('claire_alice_watch_my_little_pony_module_that_does_not_exist');
  }

  /**
   * Ensure setting the module list replaces the module list and resets internal structures.
   *
   * @covers ::setModuleList
   */
  public function testSetModuleList() {
    $fixture_module_handler = $this->getModuleHandler();
    $module_handler = $this->getMockBuilder(ModuleHandler::class)
      ->setConstructorArgs([
        $this->root, [], $this->cacheBackend,
      ])
      ->onlyMethods(['resetImplementations'])
      ->getMock();

    // Ensure we reset implementations when settings a new modules list.
    $module_handler->expects($this->once())->method('resetImplementations');

    // Make sure we're starting empty.
    $this->assertEquals([], $module_handler->getModuleList());

    // Replace the list with a prebuilt list.
    $module_handler->setModuleList($fixture_module_handler->getModuleList());

    // Ensure those changes are stored.
    $this->assertEquals($fixture_module_handler->getModuleList(), $module_handler->getModuleList());
  }

  /**
   * Tests adding a module.
   *
   * @covers ::addModule
   * @covers ::add
   */
  public function testAddModule() {

    $module_handler = $this->getMockBuilder(ModuleHandler::class)
      ->setConstructorArgs([
        $this->root, [], $this->cacheBackend,
      ])
      ->onlyMethods(['resetImplementations'])
      ->getMock();

    // Ensure we reset implementations when settings a new modules list.
    $module_handler->expects($this->once())->method('resetImplementations');

    $module_handler->addModule('module_handler_test', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test');
    $this->assertTrue($module_handler->moduleExists('module_handler_test'));
  }

  /**
   * Tests adding a profile.
   *
   * @covers ::addProfile
   * @covers ::add
   */
  public function testAddProfile() {

    $module_handler = $this->getMockBuilder(ModuleHandler::class)
      ->setConstructorArgs([
        $this->root, [], $this->cacheBackend,
      ])
      ->onlyMethods(['resetImplementations'])
      ->getMock();

    // Ensure we reset implementations when settings a new modules list.
    $module_handler->expects($this->once())->method('resetImplementations');

    // @todo this should probably fail since its a module not a profile.
    $module_handler->addProfile('module_handler_test', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test');
    $this->assertTrue($module_handler->moduleExists('module_handler_test'));
  }

  /**
   * Tests module exists returns correct module status.
   *
   * @covers ::moduleExists
   */
  public function testModuleExists() {
    $module_handler = $this->getModuleHandler();
    $this->assertTrue($module_handler->moduleExists('module_handler_test'));
    $this->assertFalse($module_handler->moduleExists('module_handler_test_added'));
  }

  /**
   * @covers ::loadAllIncludes
   */
  public function testLoadAllIncludes() {
    $this->assertTrue(TRUE);
    $module_handler = $this->getMockBuilder(ModuleHandler::class)
      ->setConstructorArgs([
        $this->root,
        [
          'module_handler_test' => [
            'type' => 'module',
            'pathname' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test/module_handler_test.info.yml',
            'filename' => 'module_handler_test.module',
          ],
        ], $this->cacheBackend,
      ])
      ->onlyMethods(['loadInclude'])
      ->getMock();

    // Ensure we reset implementations when settings a new modules list.
    $module_handler->expects($this->once())->method('loadInclude');
    $module_handler->loadAllIncludes('hook');
  }

  /**
   * @covers ::loadInclude
   *
   * Note we load code, so isolate the test.
   *
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testLoadInclude() {
    $module_handler = $this->getModuleHandler();
    // Include exists.
    $this->assertEquals(__DIR__ . '/modules/module_handler_test/hook_include.inc', $module_handler->loadInclude('module_handler_test', 'inc', 'hook_include'));
    $this->assertTrue(function_exists('module_handler_test_hook_include'));
    // Include doesn't exist.
    $this->assertFalse($module_handler->loadInclude('module_handler_test', 'install'));
  }

  /**
   * Tests invoke methods when module is enabled.
   *
   * @covers ::invoke
   */
  public function testInvokeModuleEnabled() {
    $module_handler = $this->getModuleHandler();
    $this->assertTrue($module_handler->invoke('module_handler_test', 'hook', [TRUE]), 'Installed module runs hook.');
    $this->assertFalse($module_handler->invoke('module_handler_test', 'hook', [FALSE]), 'Installed module runs hook.');
    $this->assertNull($module_handler->invoke('module_handler_test_fake', 'hook', [FALSE]), 'Installed module runs hook.');
  }

  /**
   * Tests implementations methods when module is enabled.
   *
   * @covers ::hasImplementations
   * @covers ::loadAllIncludes
   */
  public function testImplementsHookModuleEnabled() {
    $module_handler = $this->getModuleHandler();
    $this->assertTrue($module_handler->hasImplementations('hook', 'module_handler_test'), 'Installed module implementation found.');

    $module_handler->addModule('module_handler_test_added', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_added');
    $this->assertTrue($module_handler->hasImplementations('hook', 'module_handler_test_added'), 'Runtime added module with implementation in include found.');

    $module_handler->addModule('module_handler_test_no_hook', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_no_hook');
    $this->assertFalse($module_handler->hasImplementations('hook', 'module_handler_test_no_hook'), 'Missing implementation not found.');
  }

  /**
   * Tests hasImplementations.
   *
   * @covers ::hasImplementations
   */
  public function testHasImplementations() {
    $module_handler = $this->getMockBuilder(ModuleHandler::class)
      ->setConstructorArgs([$this->root, [], $this->cacheBackend])
      ->onlyMethods(['buildImplementationInfo'])
      ->getMock();
    $module_handler->expects($this->exactly(2))
      ->method('buildImplementationInfo')
      ->with('hook')
      ->willReturnOnConsecutiveCalls(
        [],
        ['mymodule' => FALSE],
      );

    // ModuleHandler::buildImplementationInfo mock returns no implementations.
    $this->assertFalse($module_handler->hasImplementations('hook'));

    // Reset static caches.
    $module_handler->resetImplementations();

    // ModuleHandler::buildImplementationInfo mock returns an implementation.
    $this->assertTrue($module_handler->hasImplementations('hook'));
  }

  /**
   * Tests getImplementations.
   *
   * @covers ::invokeAllWith
   */
  public function testCachedGetImplementations() {
    $this->cacheBackend->expects($this->exactly(1))
      ->method('get')
      ->will($this->onConsecutiveCalls(
        (object) ['data' => ['hook' => ['module_handler_test' => 'test']]]
      ));

    // Ensure buildImplementationInfo doesn't get called and that we work off cached results.
    $module_handler = $this->getMockBuilder(ModuleHandler::class)
      ->setConstructorArgs([
        $this->root, [
          'module_handler_test' => [
            'type' => 'module',
            'pathname' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test/module_handler_test.info.yml',
            'filename' => 'module_handler_test.module',
          ],
        ], $this->cacheBackend,
      ])
      ->onlyMethods(['buildImplementationInfo', 'loadInclude'])
      ->getMock();
    $module_handler->load('module_handler_test');

    $module_handler->expects($this->never())->method('buildImplementationInfo');
    $module_handler->expects($this->once())->method('loadInclude');
    $implementors = [];
    $module_handler->invokeAllWith(
      'hook',
      function (callable $hook, string $module) use (&$implementors) {
        $implementors[] = $module;
      }
    );
    $this->assertEquals(['module_handler_test'], $implementors);
  }

  /**
   * Tests getImplementations.
   *
   * @covers ::invokeAllWith
   */
  public function testCachedGetImplementationsMissingMethod() {
    $this->cacheBackend->expects($this->exactly(1))
      ->method('get')
      ->will($this->onConsecutiveCalls((object) [
        'data' => [
          'hook' => [
            'module_handler_test' => [],
            'module_handler_test_missing' => [],
          ],
        ],
      ]));

    // Ensure buildImplementationInfo doesn't get called and that we work off cached results.
    $module_handler = $this->getMockBuilder(ModuleHandler::class)
      ->setConstructorArgs([
        $this->root, [
          'module_handler_test' => [
            'type' => 'module',
            'pathname' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test/module_handler_test.info.yml',
            'filename' => 'module_handler_test.module',
          ],
        ], $this->cacheBackend,
      ])
      ->onlyMethods(['buildImplementationInfo'])
      ->getMock();
    $module_handler->load('module_handler_test');

    $module_handler->expects($this->never())->method('buildImplementationInfo');
    $implementors = [];
    $module_handler->invokeAllWith(
      'hook',
      function (callable $hook, string $module) use (&$implementors) {
        $implementors[] = $module;
      }
    );
    $this->assertEquals(['module_handler_test'], $implementors);
  }

  /**
   * Tests invoke all.
   *
   * @covers ::invokeAll
   */
  public function testInvokeAll() {
    $module_handler = $this->getModuleHandler();
    $module_handler->addModule('module_handler_test_all1', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all1');
    $module_handler->addModule('module_handler_test_all2', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all2');
    $this->assertEquals([TRUE, TRUE, TRUE], $module_handler->invokeAll('hook', [TRUE]));
  }

  /**
   * Tests that write cache calls through to cache library correctly.
   *
   * @covers ::writeCache
   */
  public function testWriteCache() {
    $module_handler = $this->getModuleHandler();
    $this->cacheBackend
      ->expects($this->exactly(2))
      ->method('get')
      ->willReturn(NULL);
    $this->cacheBackend
      ->expects($this->exactly(2))
      ->method('set')
      ->with($this->logicalOr('module_implements', 'hook_info'));
    $module_handler->invokeAllWith('hook', function (callable $hook, string $module) {});
    $module_handler->writeCache();
  }

  /**
   * Tests hook_hook_info() fetching through getHookInfo().
   *
   * @covers ::getHookInfo
   * @covers ::buildHookInfo
   */
  public function testGetHookInfo() {
    $module_handler = $this->getModuleHandler();
    // Set up some synthetic results.
    $this->cacheBackend
      ->expects($this->exactly(2))
      ->method('get')
      ->will($this->onConsecutiveCalls(
        NULL,
        (object) ['data' => ['hook_foo' => ['group' => 'hook']]]
      ));

    // Results from building from mocked environment.
    $this->assertEquals([
      'hook' => ['group' => 'hook'],
    ], $module_handler->getHookInfo());

    // Reset local cache so we get our synthetic result from the cache handler.
    $module_handler->resetImplementations();
    $this->assertEquals([
      'hook_foo' => ['group' => 'hook'],
    ], $module_handler->getHookInfo());
  }

  /**
   * Tests internal implementation cache reset.
   *
   * @covers ::resetImplementations
   */
  public function testResetImplementations() {
    $module_handler = $this->getModuleHandler();
    // Prime caches
    $module_handler->invokeAllWith('hook', function (callable $hook, string $module) {});
    $module_handler->getHookInfo();

    // Reset all caches internal and external.
    $this->cacheBackend
      ->expects($this->once())
      ->method('delete')
      ->with('hook_info');
    $this->cacheBackend
      ->expects($this->exactly(2))
      ->method('set')
      // reset sets module_implements to array() and getHookInfo later
      // populates hook_info.
      ->with($this->logicalOr('module_implements', 'hook_info'));
    $module_handler->resetImplementations();

    // Request implementation and ensure hook_info and module_implements skip
    // local caches.
    $this->cacheBackend
      ->expects($this->exactly(2))
      ->method('get')
      ->with($this->logicalOr('module_implements', 'hook_info'));
    $module_handler->invokeAllWith('hook', function (callable $hook, string $module) {});
  }

  /**
   * @covers ::getModuleDirectories
   */
  public function testGetModuleDirectories() {
    $module_handler = $this->getModuleHandler();
    $module_handler->setModuleList([]);
    $module_handler->addModule('node', 'core/modules/node');
    $this->assertEquals(['node' => $this->root . '/core/modules/node'], $module_handler->getModuleDirectories());
  }

}
