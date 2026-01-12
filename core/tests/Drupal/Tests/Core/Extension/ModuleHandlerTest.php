<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Hook\ImplementationList;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Utility\CallableResolver;
use Drupal\Tests\Core\GroupIncludesTestTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\Core\Extension\ModuleHandler.
 */
#[CoversClass(ModuleHandler::class)]
#[Group('Extension')]
#[RunTestsInSeparateProcesses]
class ModuleHandlerTest extends UnitTestCase {

  use GroupIncludesTestTrait;

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
   * @param list<string, string> $modules
   *   Module paths by module name.
   * @param array<string, array<callable-string, string>> $implementations
   *   Module names by function name implementing hook_hook().
   * @param array<string, array<string, string>> $includes
   *   Include files per hook.
   * @param array<string, array<string, string>> $group_includes
   *   Group include files per hook.
   * @param bool $loadAll
   *   TRUE to call ModuleHandler->loadAll() on the new module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandler
   *   The module handler to test.
   */
  protected function getModuleHandler($modules = [], $implementations = [], array $includes = [], array $group_includes = [], $loadAll = TRUE): ModuleHandler {
    // This only works if there's a single $hook.
    $modules['module_handler_test'] = 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test';
    $moduleList = [];
    foreach ($modules as $module => $path) {
      $filename = "$module.module";
      $moduleList[$module] = [
        'type' => 'module',
        'pathname' => "$path/$module.info.yml",
        'filename' => file_exists("$this->root/$path/$filename") ? $filename : NULL,
      ];
    }
    $keyvalue = new KeyValueMemoryFactory();
    $cache = new NullBackend('bootstrap');
    $keyvalue->get('hook_data')->set('hook_list', $implementations);
    $keyvalue->get('hook_data')->set('includes', $includes);
    $keyvalue->get('hook_data')->set('group_includes', $group_includes);
    $callableResolver = $this->createMock(CallableResolver::class);
    $callableResolver->expects($this->any())
      ->method('getCallableFromDefinition')
      ->willReturnCallback(fn ($definition) => $definition);
    $moduleHandler = new ModuleHandler($this->root, $moduleList, $keyvalue, $callableResolver, $cache);
    if ($loadAll) {
      $moduleHandler->loadAll();
    }
    return $moduleHandler;
  }

  /**
   * Tests loading a module.
   */
  public function testLoadModule(): void {
    $moduleList = [
      'module_handler_test_added' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_added',
    ];
    $module_handler = $this->getModuleHandler($moduleList);
    $this->assertTrue($module_handler->load('module_handler_test'));
    $this->assertTrue(function_exists('module_handler_test_hook'));

    $this->assertTrue($module_handler->load('module_handler_test_added'));
    $this->assertTrue(function_exists('module_handler_test_added_helper'), 'Function exists after being loaded.');
    $this->assertTrue($module_handler->load('module_handler_test_added'));

    $this->assertFalse($module_handler->load('module_handler_test_dne'), 'Non-existent modules returns false.');
  }

  /**
   * Tests loading all modules.
   */
  #[IgnoreDeprecations]
  public function testLoadAllModules(): void {
    $moduleList = [
      'module_handler_test_all1' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all1',
      'module_handler_test_all2' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all2',
    ];
    $module_handler = $this->getModuleHandler($moduleList);
    $module_handler->loadAll();
    $this->assertTrue(function_exists('module_handler_test_all1_hook'), 'Function exists after being loaded.');
    $this->assertTrue(function_exists('module_handler_test_all2_hook'), 'Function exists after being loaded.');
  }

  /**
   * Tests reload method.
   *
   * @legacy-covers ::reload
   */
  public function testModuleReloading(): void {
    $module_handler = $this->getMockBuilder(ModuleHandler::class)
      ->setConstructorArgs([
        $this->root,
        [
          'module_handler_test' => [
            'type' => 'module',
            'pathname' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test/module_handler_test.info.yml',
            'filename' => 'module_handler_test.module',
          ],
        ],
        new KeyValueMemoryFactory(),
        $this->createMock(CallableResolver::class),
        new NullBackend('bootstrap'),
      ])
      ->onlyMethods(['load'])
      ->getMock();
    $calls = [
      'module_handler_test',
    ];
    $module_handler->expects($this->once())
      ->method('load')
      ->with($this->callback(function (string $module) use (&$calls): bool {
        return $module === array_shift($calls);
      }));
    $module_handler->reload();
  }

  /**
   * Tests isLoaded accessor.
   */
  public function testIsLoaded(): void {
    $module_handler = $this->getModuleHandler(loadAll: FALSE);
    $this->assertFalse($module_handler->isLoaded());
    $module_handler->loadAll();
    $this->assertTrue($module_handler->isLoaded());
  }

  /**
   * Confirm we get back the modules set in the constructor.
   */
  public function testGetModuleList(): void {
    $this->assertEquals($this->getModuleHandler()->getModuleList(), [
      'module_handler_test' => new Extension($this->root, 'module', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test/module_handler_test.info.yml', 'module_handler_test.module'),
    ]);
  }

  /**
   * Confirm we get back a module from the module list.
   */
  public function testGetModuleWithExistingModule(): void {
    $this->assertEquals($this->getModuleHandler()->getModule('module_handler_test'), new Extension($this->root, 'module', 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test/module_handler_test.info.yml', 'module_handler_test.module'));
  }

  /**
   * Tests get module with non existing module.
   */
  public function testGetModuleWithNonExistingModule(): void {
    $this->expectException(UnknownExtensionException::class);
    $this->getModuleHandler()->getModule('claire_alice_watch_my_little_pony_module_that_does_not_exist');
  }

  /**
   * Ensure setting the module list replaces the module list and resets internal structures.
   */
  public function testSetModuleList(): void {
    $fixture_module_handler = $this->getModuleHandler();
    $module_handler = $this->getMockBuilder(ModuleHandler::class)
      ->setConstructorArgs([
        $this->root,
        [],
        new KeyValueMemoryFactory(),
        $this->createMock(CallableResolver::class),
        new NullBackend('bootstrap'),
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
   * Tests module exists returns correct module status.
   */
  public function testModuleExists(): void {
    $module_handler = $this->getModuleHandler();
    $this->assertTrue($module_handler->moduleExists('module_handler_test'));
    $this->assertFalse($module_handler->moduleExists('module_handler_test_added'));
  }

  /**
   * Tests load all includes.
   */
  #[IgnoreDeprecations]
  public function testLoadAllIncludes(): void {
    $this->expectDeprecation('ModuleHandler::loadAllIncludes() is deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3536432');
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
        ],
        new KeyValueMemoryFactory(),
        $this->createMock(CallableResolver::class),
        new NullBackend('bootstrap'),
      ])
      ->onlyMethods(['loadInclude'])
      ->getMock();

    // Ensure we reset implementations when settings a new modules list.
    $module_handler->expects($this->once())->method('loadInclude');
    $module_handler->loadAllIncludes('hook');
  }

  /**
   * Tests loadInclude().
   *
   * Note we load code, so isolate the test.
   */
  #[PreserveGlobalState(FALSE)]
  #[RunInSeparateProcess]
  public function testLoadInclude(): void {
    $module_handler = $this->getModuleHandler();
    // Include exists.
    $this->assertEquals(__DIR__ . '/modules/module_handler_test/hook_include.inc', $module_handler->loadInclude('module_handler_test', 'inc', 'hook_include'));
    $this->assertTrue(function_exists('module_handler_test_hook_include'));
    // Include doesn't exist.
    $this->assertFalse($module_handler->loadInclude('module_handler_test', 'install'));
  }

  /**
   * Tests invoke methods when module is enabled.
   */
  public function testInvokeModuleEnabled(): void {
    $module_handler = $this->getModuleHandler();
    $module_handler->loadAll();
    $this->assertTrue($module_handler->invoke('module_handler_test', 'hook', [TRUE]), 'Installed module runs hook.');
    $this->assertFalse($module_handler->invoke('module_handler_test', 'hook', [FALSE]), 'Installed module runs hook.');
    $this->assertNull($module_handler->invoke('module_handler_test_fake', 'hook', [FALSE]), 'Installed module runs hook.');
  }

  /**
   * Tests implementations methods when module is enabled.
   *
   * @legacy-covers ::hasImplementations
   */
  public function testImplementsHookModuleEnabled(): void {
    $implementations = [
      'hook' => [
        'module_handler_test_hook' => 'module_handler_test',
        'module_handler_test_added_hook' => 'module_handler_test_added',
      ],
    ];
    $moduleList = [
      'module_handler_test_added' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_added',
      'module_handler_test_no_hook' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_no_hook',
    ];
    $includes_per_function = [
      'hook' => [
        'module_handler_test_added_hook' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_added/module_handler_test_added.hook.inc',
      ],
    ];
    $module_handler = $this->getModuleHandler($moduleList, $implementations, $includes_per_function);

    $this->assertTrue($module_handler->hasImplementations('hook', 'module_handler_test'), 'Installed module implementation found.');
    $this->assertTrue($module_handler->hasImplementations('hook', 'module_handler_test_added'), 'Runtime added module with implementation in include found.');
    $this->assertFalse($module_handler->hasImplementations('hook', 'module_handler_test_no_hook'), 'Missing implementation not found.');
  }

  /**
   * Tests invoke all.
   */
  #[IgnoreDeprecations]
  public function testInvokeAll(): void {
    $implementations = [
      'hook' => [
        'module_handler_test_hook' => 'module_handler_test',
        'module_handler_test_all1_hook' => 'module_handler_test_all1',
        'module_handler_test_all2_hook' => 'module_handler_test_all2',
      ],
    ];
    $moduleList = [
      'module_handler_test_all1' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all1',
      'module_handler_test_all2' => 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all2',
    ];
    $module_handler = $this->getModuleHandler($moduleList, $implementations);
    $this->assertEquals([TRUE, TRUE, TRUE], $module_handler->invokeAll('hook', [TRUE]));
  }

  /**
   * Tests hasImplementations.
   */
  public function testHasImplementations(): void {
    $implementations = [
      'some_hook' => [
        TestHookClass::class . '::someMethod' => 'some_module',
      ],
      // Set up a hook list closure with empty result.
      // This can theoretically happen if the implementations are for modules that
      // are not installed.
      'empty_hook' => [],
    ];

    $module_handler = $this->getModuleHandler([], $implementations);
    $module_handler->setModuleList(['some_module' => TRUE]);
    $r = new \ReflectionObject($module_handler);
    $get_lists = fn () => $r->getProperty('hookImplementationLists')->getValue($module_handler);

    $this->assertSame([], $get_lists());
    $this->assertTrue($module_handler->hasImplementations('some_hook'));
    $this->assertEquals(
      [
        'some_hook' => new ImplementationList([TestHookClass::class . '::someMethod'], ['some_module']),
      ],
      $get_lists(),
    );
    $this->assertFalse($module_handler->hasImplementations('unknown_hook'));
    $this->assertEquals(
      [
        'some_hook' => new ImplementationList([TestHookClass::class . '::someMethod'], ['some_module']),
        'unknown_hook' => new ImplementationList([], []),
      ],
      $get_lists(),
    );
    $this->assertFalse($module_handler->hasImplementations('empty_hook'));
    $this->assertEquals(
      [
        'some_hook' => new ImplementationList([TestHookClass::class . '::someMethod'], ['some_module']),
        'unknown_hook' => new ImplementationList([], []),
        'empty_hook' => new ImplementationList([], []),
      ],
      $get_lists(),
    );
  }

  /**
   * Tests get module directories.
   */
  public function testGetModuleDirectories(): void {
    $moduleList = [
      'node' => 'core/modules/node',
    ];
    $module_handler = $this->getModuleHandler($moduleList);
    $moduleDirectories = [
      'node' => $this->root . '/core/modules/node',
      'module_handler_test' => $this->root . '/core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test',
    ];
    $this->assertEquals($moduleDirectories, $module_handler->getModuleDirectories());
  }

  /**
   * Tests group includes.
   */
  #[IgnoreDeprecations]
  public function testGroupIncludes(): void {
    self::setupGroupIncludes();
    $this->expectDeprecation('Autoloading hooks in the file (vfs://drupal_root/test_module.tokens.inc) is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Move the functions in this file to either the .module file or other appropriate location. See https://www.drupal.org/node/3489765');
    $moduleHandler = $this->getModuleHandler([], ['token_info' => ['test_module_token_info' => 'test_module']], [], ['token_info' => self::GROUP_INCLUDES['token_info']]);
    $this->assertFalse(function_exists('_test_module_helper'));
    $moduleHandler->invokeAll('token_info');
    $this->assertTrue(function_exists('_test_module_helper'));
  }

}

/**
 * Class used to test ModuleHandler::hasImplementations()
 */
class TestHookClass {

  /**
   * Example method.
   */
  public static function someMethod(): void {
  }

}
