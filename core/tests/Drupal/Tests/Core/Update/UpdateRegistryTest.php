<?php

namespace Drupal\Tests\Core\Update;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Update\RemovedPostUpdateNameException;
use Drupal\Core\Update\UpdateRegistry;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\Core\Update\UpdateRegistry
 * @group Update
 *
 * Note we load code, so isolate the tests.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class UpdateRegistryTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $settings = [];
    $settings['extension_discovery_scan_tests'] = TRUE;
    new Settings($settings);
  }

  /**
   * Sets up some modules with some update functions.
   */
  protected function setupBasicModules() {
    $info_a = <<<'EOS'
type: module
name: Module A
core_version_requirement: '*'
EOS;

    $info_b = <<<'EOS'
type: module
name: Module B
core_version_requirement: '*'
EOS;

    $info_c = <<<'EOS'
type: module
name: Module C
core_version_requirement: '*'
EOS;

    $module_a = <<<'EOS'
<?php

/**
 * Module A update B.
 */
function module_a_post_update_b() {
}

/**
 * Module A update A.
 */
function module_a_post_update_a() {
}

EOS;
    $module_b = <<<'EOS'
<?php

/**
 * Module B update A.
 */
function module_b_post_update_a() {
}

/**
 * Implements hook_removed_post_updates().
 */
function module_b_removed_post_updates() {
  return [
    'module_b_post_update_b' => '8.9.0',
    'module_b_post_update_c' => '8.9.0',
  ];
}

EOS;

    $module_c = <<<'EOS'
<?php

/**
 * Module C update A.
 */
function module_c_post_update_a() {
}

/**
 * Module C update B.
 */
function module_c_post_update_b() {
}

/**
 * Implements hook_removed_post_updates().
 */
function module_c_removed_post_updates() {
  return [
    'module_c_post_update_b' => '8.9.0',
    'module_c_post_update_c' => '8.9.0',
  ];
}

EOS;
    vfsStream::setup('drupal');
    vfsStream::create([
      'sites' => [
        'default' => [
          'modules' => [
            'module_a' => [
              'module_a.post_update.php' => $module_a,
              'module_a.info.yml' => $info_a,
            ],
            'module_b' => [
              'module_b.post_update.php' => $module_b,
              'module_b.info.yml' => $info_b,
            ],
            'module_c' => [
              'module_c.post_update.php' => $module_c,
              'module_c.info.yml' => $info_c,
            ],
          ],
        ],
      ],
    ]);
  }

  /**
   * @covers ::getPendingUpdateFunctions
   */
  public function testGetPendingUpdateFunctionsNoExistingUpdates() {
    $this->setupBasicModules();

    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])->willReturn([]);
    $key_value = $key_value->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a',
      'module_b',
    ], $key_value, FALSE);

    $this->assertEquals([
      'module_a_post_update_a',
      'module_a_post_update_b',
      'module_b_post_update_a',
    ], $update_registry->getPendingUpdateFunctions());
  }

  /**
   * @covers ::getPendingUpdateFunctions
   */
  public function testGetPendingUpdateFunctionsWithLoadedModulesButNotEnabled() {
    $this->setupBasicModules();

    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])->willReturn([]);
    $key_value = $key_value->reveal();

    // Preload modules to ensure that ::getAvailableUpdateFunctions filters out
    // not enabled modules.
    include_once 'vfs://drupal/sites/default/modules/module_a/module_a.post_update.php';
    include_once 'vfs://drupal/sites/default/modules/module_b/module_b.post_update.php';

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a',
    ], $key_value, FALSE);

    $this->assertEquals([
      'module_a_post_update_a',
      'module_a_post_update_b',
    ], $update_registry->getPendingUpdateFunctions());
  }

  /**
   * @covers ::getPendingUpdateFunctions
   */
  public function testGetPendingUpdateFunctionsExistingUpdates() {
    $this->setupBasicModules();

    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])->willReturn(['module_a_post_update_a']);
    $key_value = $key_value->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a',
      'module_b',
    ], $key_value, FALSE);

    $this->assertEquals(array_values([
      'module_a_post_update_b',
      'module_b_post_update_a',
    ]), array_values($update_registry->getPendingUpdateFunctions()));

  }

  /**
   * @covers ::getPendingUpdateInformation
   */
  public function testGetPendingUpdateInformation() {
    $this->setupBasicModules();

    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])->willReturn([]);
    $key_value = $key_value->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a',
      'module_b',
    ], $key_value, FALSE);

    $expected = [];
    $expected['module_a']['pending']['a'] = 'Module A update A.';
    $expected['module_a']['pending']['b'] = 'Module A update B.';
    $expected['module_a']['start'] = 'a';
    $expected['module_b']['pending']['a'] = 'Module B update A.';
    $expected['module_b']['start'] = 'a';

    $this->assertEquals($expected, $update_registry->getPendingUpdateInformation());
  }

  /**
   * @covers ::getPendingUpdateInformation
   */
  public function testGetPendingUpdateInformationWithExistingUpdates() {
    $this->setupBasicModules();

    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])->willReturn(['module_a_post_update_a']);
    $key_value = $key_value->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a',
      'module_b',
    ], $key_value, FALSE);

    $expected = [];
    $expected['module_a']['pending']['b'] = 'Module A update B.';
    $expected['module_a']['start'] = 'b';
    $expected['module_b']['pending']['a'] = 'Module B update A.';
    $expected['module_b']['start'] = 'a';

    $this->assertEquals($expected, $update_registry->getPendingUpdateInformation());
  }

  /**
   * @covers ::getPendingUpdateInformation
   */
  public function testGetPendingUpdateInformationWithRemovedUpdates() {
    $this->setupBasicModules();

    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])->willReturn(['module_a_post_update_a']);
    $key_value = $key_value->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_c',
    ], $key_value, FALSE);

    $this->expectException(RemovedPostUpdateNameException::class);
    $update_registry->getPendingUpdateInformation();
  }

  /**
   * @covers ::getModuleUpdateFunctions
   */
  public function testGetModuleUpdateFunctions() {
    $this->setupBasicModules();
    $key_value = $this->prophesize(KeyValueStoreInterface::class)->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a',
      'module_b',
    ], $key_value, FALSE);

    $this->assertEquals(['module_a_post_update_a', 'module_a_post_update_b'], array_values($update_registry->getModuleUpdateFunctions('module_a')));
    $this->assertEquals(['module_b_post_update_a'], array_values($update_registry->getModuleUpdateFunctions('module_b')));
  }

  /**
   * @covers ::registerInvokedUpdates
   */
  public function testRegisterInvokedUpdatesWithoutExistingUpdates() {
    $this->setupBasicModules();
    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])
      ->willReturn([])
      ->shouldBeCalledTimes(1);
    $key_value->set('existing_updates', ['module_a_post_update_a'])
      ->willReturn(NULL)
      ->shouldBeCalledTimes(1);
    $key_value = $key_value->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a',
      'module_b',
    ], $key_value, FALSE);
    $update_registry->registerInvokedUpdates(['module_a_post_update_a']);
  }

  /**
   * @covers ::registerInvokedUpdates
   */
  public function testRegisterInvokedUpdatesWithMultiple() {
    $this->setupBasicModules();
    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])
      ->willReturn([])
      ->shouldBeCalledTimes(1);
    $key_value->set('existing_updates', ['module_a_post_update_a', 'module_a_post_update_b'])
      ->willReturn(NULL)
      ->shouldBeCalledTimes(1);
    $key_value = $key_value->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a',
      'module_b',
    ], $key_value, FALSE);
    $update_registry->registerInvokedUpdates(['module_a_post_update_a', 'module_a_post_update_b']);
  }

  /**
   * @covers ::registerInvokedUpdates
   */
  public function testRegisterInvokedUpdatesWithExistingUpdates() {
    $this->setupBasicModules();
    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])
      ->willReturn(['module_a_post_update_b'])
      ->shouldBeCalledTimes(1);
    $key_value->set('existing_updates', ['module_a_post_update_b', 'module_a_post_update_a'])
      ->willReturn(NULL)
      ->shouldBeCalledTimes(1);
    $key_value = $key_value->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a',
      'module_b',
    ], $key_value, FALSE);
    $update_registry->registerInvokedUpdates(['module_a_post_update_a']);
  }

  /**
   * @covers ::filterOutInvokedUpdatesByModule
   */
  public function testFilterOutInvokedUpdatesByModule() {
    $this->setupBasicModules();
    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])
      ->willReturn(['module_a_post_update_b', 'module_a_post_update_a', 'module_b_post_update_a'])
      ->shouldBeCalledTimes(1);
    $key_value->set('existing_updates', ['module_b_post_update_a'])
      ->willReturn(NULL)
      ->shouldBeCalledTimes(1);
    $key_value = $key_value->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a',
      'module_b',
    ], $key_value, FALSE);

    $update_registry->filterOutInvokedUpdatesByModule('module_a');
  }

}
