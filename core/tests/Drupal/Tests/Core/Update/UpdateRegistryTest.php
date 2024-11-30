<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Update;

use Drupal\Core\Extension\ThemeHandlerInterface;
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
  protected function setUp(): void {
    parent::setUp();

    $settings = [];
    $settings['extension_discovery_scan_tests'] = TRUE;
    new Settings($settings);
  }

  /**
   * Sets up some extensions with some update functions.
   */
  protected function setupBasicExtensions() {
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

    $info_d = <<<'EOS'
type: theme
name: Theme D
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

    $theme_d = <<<'EOS'
<?php

/**
 * Theme D update B.
 */
function theme_d_post_update_b() {
}

/**
 * Theme D update C.
 */
function theme_d_post_update_c() {
}

/**
 * Implements hook_removed_post_updates().
 */
function theme_d_removed_post_updates() {
  return [
    'theme_d_post_update_a' => '8.9.0',
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
          'themes' => [
            'theme_d' => [
              'theme_d.post_update.php' => $theme_d,
              'theme_d.info.yml' => $info_d,
            ],
          ],
        ],
      ],
    ]);
  }

  /**
   * @covers ::getPendingUpdateFunctions
   */
  public function testGetPendingUpdateFunctionsNoExistingUpdates(): void {
    $this->setupBasicExtensions();

    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])->willReturn([]);
    $key_value = $key_value->reveal();

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->listInfo()->willReturn([
      'theme_d' => [
        'type' => 'theme',
        'pathname' => 'core/themes/theme_d/theme_d.info.yml',
      ],
    ]);
    $theme_handler = $theme_handler->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_a/module_a.info.yml',
          'filename' => 'module_a.module',
        ],
      'module_b' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_b/module_b.info.yml',
          'filename' => 'module_b.module',
        ],
    ], $key_value, $theme_handler, 'post_update');

    // Confirm the updates are sorted alphabetically.
    $this->assertEquals([
      'module_a_post_update_a',
      'module_a_post_update_b',
      'module_b_post_update_a',
      'theme_d_post_update_b',
      'theme_d_post_update_c',
    ], $update_registry->getPendingUpdateFunctions());
  }

  /**
   * @covers ::getPendingUpdateFunctions
   */
  public function testGetPendingUpdateFunctionsWithLoadedModulesButNotEnabled(): void {
    $this->setupBasicExtensions();

    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])->willReturn([]);
    $key_value = $key_value->reveal();

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->listInfo()->willReturn([]);
    $theme_handler = $theme_handler->reveal();

    // Preload modules to ensure that ::getAvailableUpdateFunctions filters out
    // not enabled modules.
    include_once 'vfs://drupal/sites/default/modules/module_a/module_a.post_update.php';
    include_once 'vfs://drupal/sites/default/modules/module_b/module_b.post_update.php';

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_a/module_a.info.yml',
          'filename' => 'module_a.module',
        ],
    ], $key_value, $theme_handler, 'post_update');

    // Confirm the updates are sorted alphabetically.
    $this->assertEquals([
      'module_a_post_update_a',
      'module_a_post_update_b',
    ], $update_registry->getPendingUpdateFunctions());
  }

  /**
   * @covers ::getPendingUpdateFunctions
   */
  public function testGetPendingUpdateFunctionsExistingUpdates(): void {
    $this->setupBasicExtensions();

    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])->willReturn([
      'module_a_post_update_a',
      'theme_d_post_update_a',
      'theme_d_post_update_b',
    ]);
    $key_value = $key_value->reveal();

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->listInfo()->willReturn([
      'theme_d' => [
        'type' => 'theme',
        'pathname' => 'core/themes/theme_d/theme_d.info.yml',
      ],
    ]);
    $theme_handler = $theme_handler->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_a/module_a.info.yml',
          'filename' => 'module_a.module',
        ],
      'module_b' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_b/module_b.info.yml',
          'filename' => 'module_b.module',
        ],
    ], $key_value, $theme_handler, 'post_update');

    // Confirm the updates are sorted alphabetically.
    $this->assertEquals(array_values([
      'module_a_post_update_b',
      'module_b_post_update_a',
      'theme_d_post_update_c',
    ]), array_values($update_registry->getPendingUpdateFunctions()));

  }

  /**
   * @covers ::getPendingUpdateInformation
   */
  public function testGetPendingUpdateInformation(): void {
    $this->setupBasicExtensions();

    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])->willReturn([]);
    $key_value = $key_value->reveal();

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->listInfo()->willReturn([
      'theme_d' => [
        'type' => 'theme',
        'pathname' => 'core/themes/theme_d/theme_d.info.yml',
      ],
    ]);
    $theme_handler = $theme_handler->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_a/module_a.info.yml',
          'filename' => 'module_a.module',
        ],
      'module_b' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_b/module_b.info.yml',
          'filename' => 'module_b.module',
        ],
    ], $key_value, $theme_handler, 'post_update');

    // Confirm the updates are sorted alphabetically.
    $expected = [];
    $expected['module_a']['pending']['a'] = 'Module A update A.';
    $expected['module_a']['pending']['b'] = 'Module A update B.';
    $expected['module_a']['start'] = 'a';
    $expected['module_b']['pending']['a'] = 'Module B update A.';
    $expected['module_b']['start'] = 'a';
    $expected['theme_d']['pending']['b'] = 'Theme D update B.';
    $expected['theme_d']['pending']['c'] = 'Theme D update C.';
    $expected['theme_d']['start'] = 'b';

    $this->assertEquals($expected, $update_registry->getPendingUpdateInformation());
  }

  /**
   * @covers ::getPendingUpdateInformation
   */
  public function testGetPendingUpdateInformationWithExistingUpdates(): void {
    $this->setupBasicExtensions();

    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])->willReturn([
      'module_a_post_update_a',
      'theme_d_post_update_a',
      'theme_d_post_update_b',
    ]);
    $key_value = $key_value->reveal();

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->listInfo()->willReturn([
      'theme_d' => [
        'type' => 'theme',
        'pathname' => 'core/themes/theme_d/theme_d.info.yml',
      ],
    ]);
    $theme_handler = $theme_handler->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_a/module_a.info.yml',
          'filename' => 'module_a.module',
        ],
      'module_b' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_b/module_b.info.yml',
          'filename' => 'module_b.module',
        ],
    ], $key_value, $theme_handler, 'post_update');

    // Confirm the updates are sorted alphabetically.
    $expected = [];
    $expected['module_a']['pending']['b'] = 'Module A update B.';
    $expected['module_a']['start'] = 'b';
    $expected['module_b']['pending']['a'] = 'Module B update A.';
    $expected['module_b']['start'] = 'a';
    $expected['theme_d']['pending']['c'] = 'Theme D update C.';
    $expected['theme_d']['start'] = 'c';

    $this->assertEquals($expected, $update_registry->getPendingUpdateInformation());
  }

  /**
   * @covers ::getPendingUpdateInformation
   */
  public function testGetPendingUpdateInformationWithRemovedUpdates(): void {
    $this->setupBasicExtensions();

    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])->willReturn(['module_a_post_update_a']);
    $key_value = $key_value->reveal();

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->listInfo()->willReturn([]);
    $theme_handler = $theme_handler->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_c' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_c/module_c.info.yml',
          'filename' => 'module_c.module',
        ],
    ], $key_value, $theme_handler, 'post_update');

    $this->expectException(RemovedPostUpdateNameException::class);
    $update_registry->getPendingUpdateInformation();
  }

  /**
   * @covers ::getUpdateFunctions
   */
  public function testGetUpdateFunctions(): void {
    $this->setupBasicExtensions();
    $key_value = $this->prophesize(KeyValueStoreInterface::class)->reveal();

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->listInfo()->willReturn([
      'theme_d' => [
        'type' => 'theme',
        'pathname' => 'core/themes/theme_d/theme_d.info.yml',
      ],
    ]);
    $theme_handler = $theme_handler->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_a/module_a.info.yml',
          'filename' => 'module_a.module',
        ],
      'module_b' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_b/module_b.info.yml',
          'filename' => 'module_b.module',
        ],
    ], $key_value, $theme_handler, 'post_update');

    $this->assertEquals(['module_a_post_update_a', 'module_a_post_update_b'], array_values($update_registry->getUpdateFunctions('module_a')));
    $this->assertEquals(['module_b_post_update_a'], array_values($update_registry->getUpdateFunctions('module_b')));
    $this->assertEquals(['theme_d_post_update_b', 'theme_d_post_update_c'], array_values($update_registry->getUpdateFunctions('theme_d')));
  }

  /**
   * @covers ::registerInvokedUpdates
   */
  public function testRegisterInvokedUpdatesWithoutExistingUpdates(): void {
    $this->setupBasicExtensions();
    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])
      ->willReturn([])
      ->shouldBeCalledTimes(1);
    $key_value->set('existing_updates', ['module_a_post_update_a'])
      ->willReturn(NULL)
      ->shouldBeCalledTimes(1);
    $key_value = $key_value->reveal();

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->listInfo()->willReturn([
      'theme_d' => [
        'type' => 'theme',
        'pathname' => 'core/themes/theme_d/theme_d.info.yml',
      ],
    ]);
    $theme_handler = $theme_handler->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_a/module_a.info.yml',
          'filename' => 'module_a.module',
        ],
      'module_b' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_b/module_b.info.yml',
          'filename' => 'module_b.module',
        ],
    ], $key_value, $theme_handler, 'post_update');
    $update_registry->registerInvokedUpdates(['module_a_post_update_a']);
  }

  /**
   * @covers ::registerInvokedUpdates
   */
  public function testRegisterInvokedUpdatesWithMultiple(): void {
    $this->setupBasicExtensions();
    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])
      ->willReturn([])
      ->shouldBeCalledTimes(1);
    $key_value->set('existing_updates', ['module_a_post_update_a', 'module_a_post_update_b', 'theme_d_post_update_c'])
      ->willReturn(NULL)
      ->shouldBeCalledTimes(1);
    $key_value = $key_value->reveal();

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->listInfo()->willReturn([
      'theme_d' => [
        'type' => 'theme',
        'pathname' => 'core/themes/theme_d/theme_d.info.yml',
      ],
    ]);
    $theme_handler = $theme_handler->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_a/module_a.info.yml',
          'filename' => 'module_a.module',
        ],
      'module_b' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_b/module_b.info.yml',
          'filename' => 'module_b.module',
        ],
    ], $key_value, $theme_handler, 'post_update');
    $update_registry->registerInvokedUpdates(['module_a_post_update_a', 'module_a_post_update_b', 'theme_d_post_update_c']);
  }

  /**
   * @covers ::registerInvokedUpdates
   */
  public function testRegisterInvokedUpdatesWithExistingUpdates(): void {
    $this->setupBasicExtensions();
    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])
      ->willReturn(['module_a_post_update_b'])
      ->shouldBeCalledTimes(1);
    $key_value->set('existing_updates', ['module_a_post_update_b', 'module_a_post_update_a'])
      ->willReturn(NULL)
      ->shouldBeCalledTimes(1);
    $key_value = $key_value->reveal();

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->listInfo()->willReturn([]);
    $theme_handler = $theme_handler->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_a/module_a.info.yml',
          'filename' => 'module_a.module',
        ],
      'module_b' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_b/module_b.info.yml',
          'filename' => 'module_b.module',
        ],
    ], $key_value, $theme_handler, 'post_update');
    $update_registry->registerInvokedUpdates(['module_a_post_update_a']);
  }

  /**
   * @covers ::filterOutInvokedUpdatesByExtension
   */
  public function testFilterOutInvokedUpdatesByExtension(): void {
    $this->setupBasicExtensions();
    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])
      ->willReturn(['module_a_post_update_b', 'module_a_post_update_a', 'module_b_post_update_a', 'theme_d_post_update_c'])
      ->shouldBeCalledTimes(1);
    $key_value->set('existing_updates', ['module_b_post_update_a', 'theme_d_post_update_c'])
      ->willReturn(NULL)
      ->shouldBeCalledTimes(1);
    $key_value = $key_value->reveal();

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->listInfo()->willReturn([
      'theme_d' => [
        'type' => 'theme',
        'pathname' => 'core/themes/theme_d/theme_d.info.yml',
      ],
    ]);
    $theme_handler = $theme_handler->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_a/module_a.info.yml',
          'filename' => 'module_a.module',
        ],
      'module_b' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_b/module_b.info.yml',
          'filename' => 'module_b.module',
        ],
    ], $key_value, $theme_handler, 'post_update');
    $update_registry->filterOutInvokedUpdatesByExtension('module_a');
  }

  /**
   * @covers ::getPendingUpdateFunctions
   */
  public function testGetPendingCustomUpdateFunctions(): void {
    // Set up a simplified module structure with custom update hooks.
    $info_a = <<<'EOS'
type: module
name: Module A
core_version_requirement: '*'
EOS;

    $info_d = <<<'EOS'
type: theme
name: Theme D
EOS;

    $module_a = <<<'EOS'
<?php

/**
 * Module A update A.
 */
function module_a_custom_update_a() {
}

EOS;

    $theme_d = <<<'EOS'
<?php

/**
 * Theme D update B.
 */
function theme_d_custom_update_a() {
}

EOS;
    vfsStream::setup('drupal');
    vfsStream::create([
      'sites' => [
        'default' => [
          'modules' => [
            'module_a' => [
              'module_a.custom_update.php' => $module_a,
              'module_a.info.yml' => $info_a,
            ],
          ],
          'themes' => [
            'theme_d' => [
              'theme_d.custom_update.php' => $theme_d,
              'theme_d.info.yml' => $info_d,
            ],
          ],
        ],
      ],
    ]);

    $key_value = $this->prophesize(KeyValueStoreInterface::class);
    $key_value->get('existing_updates', [])->willReturn([]);
    $key_value = $key_value->reveal();

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->listInfo()->willReturn([
      'theme_d' => [
        'type' => 'theme',
        'pathname' => 'core/themes/theme_d/theme_d.info.yml',
      ],
    ]);
    $theme_handler = $theme_handler->reveal();

    $update_registry = new UpdateRegistry('vfs://drupal', 'sites/default', [
      'module_a' =>
        [
          'type' => 'module',
          'pathname' => 'core/modules/module_a/module_a.info.yml',
          'filename' => 'module_a.module',
        ],
    ], $key_value, $theme_handler, 'custom_update');

    // Themes are not supported.
    $this->assertEquals([
      'module_a_custom_update_a',
    ], $update_registry->getPendingUpdateFunctions());
  }

}
