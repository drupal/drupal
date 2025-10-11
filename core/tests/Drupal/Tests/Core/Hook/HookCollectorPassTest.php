<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Hook;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ProceduralCall;
use Drupal\Core\Hook\HookCollectorPass;
use Drupal\Tests\Core\GroupIncludesTestTrait;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests Drupal\Core\Hook\HookCollectorPass.
 */
#[CoversClass(HookCollectorPass::class)]
#[Group('Hook')]
class HookCollectorPassTest extends UnitTestCase {

  use GroupIncludesTestTrait;

  /**
   * Tests collect all hook implementations.
   *
   * @legacy-covers ::collectAllHookImplementations
   * @legacy-covers ::filterIterator
   */
  public function testCollectAllHookImplementations(): void {
    vfsStream::setup('drupal_root');
    $files = [
      'modules/test_module/test_module_info.yml',
      // This creates a submodule which is not installed.
      'modules/test_module/test_sub_module/test_sub_module.info.yml',
    ];
    $file_data = [];
    foreach ($files as &$filename) {
      NestedArray::setValue($file_data, explode('/', $filename), '');
    }
    vfsStream::create($file_data);
    $module_filenames = [
      'test_module' => ['pathname' => 'vfs://drupal_root/modules/test_module/test_module_info.yml'],
    ];
    // This directory, however, should be included.
    mkdir('vfs://drupal_root/modules/test_module/includes');
    file_put_contents('vfs://drupal_root/modules/test_module/includes/test_module.inc', <<<__EOF__
<?php

function test_module_test_hook();

__EOF__
    );
    // This is the not installed submodule.
    file_put_contents('vfs://drupal_root/modules/test_module/test_sub_module/test_sub_module.module', <<<__EOF__
<?php

function test_module_should_be_skipped();

__EOF__
    );

    $container = new ContainerBuilder();
    $container->setParameter('container.modules', $module_filenames);
    (new HookCollectorPass())->process($container);

    $this->assertEquals(
      ['test_module_test_hook' => 'test_module'],
      $container->getParameter('.hook_data')['hook_list']['test_hook'],
    );
    $this->assertEquals(['test_hook' => ['vfs://drupal_root/modules/test_module/includes/test_module.inc']], $container->getParameter('.hook_data')['includes']);
  }

  /**
   * Tests group includes.
   *
   * @legacy-covers ::process
   * @legacy-covers ::collectModuleHookImplementations
   */
  public function testGroupIncludes(): void {
    $module_filenames = self::setupGroupIncludes();

    $container = new ContainerBuilder();
    $container->setParameter('container.modules', $module_filenames);
    (new HookCollectorPass())->process($container);

    $expected_hook_list = [
      'hook_info' => [
        'test_module_hook_info' => 'test_module',
      ],
      'token_info' => [
        'test_module_token_info' => 'test_module',
      ],
    ];
    $hook_data = $container->getParameter('.hook_data');
    $this->assertEquals($expected_hook_list, $hook_data['hook_list']);
    // Assert that the group include is not duplicated into the includes list.
    $this->assertEquals([], $hook_data['includes']);
    $this->assertEquals(['token_info' => ['vfs://drupal_root/test_module.tokens.inc']], $hook_data['group_includes']);
  }

  /**
   * Tests prefix ownership of procedural hooks.
   *
   * @legacy-covers ::process
   * @legacy-covers ::collectModuleHookImplementations
   */
  public function testPrefixOwnership(): void {
    vfsStream::setup('drupal_root');
    $files = [
      'modules/test_module/test_module.info.yml',
      'modules/test_module_theme/test_module_theme.info.yml',
    ];
    $file_data = [];
    foreach ($files as &$filename) {
      NestedArray::setValue($file_data, explode('/', $filename), '');
    }
    vfsStream::create($file_data);
    $module_filenames = [
      'test_module' => ['pathname' => 'vfs://drupal_root/modules/test_module/test_module.info.yml'],
      'test_module_theme' => ['pathname' => 'vfs://drupal_root/modules/test_module_theme/test_module_theme.info.yml'],
    ];
    file_put_contents('vfs://drupal_root/modules/test_module/test_module.module', <<<__EOF__
<?php

function test_module_theme_suggestions_alter();

__EOF__
    );
    $implementations['theme_suggestions_alter'][ProceduralCall::class]['test_module_theme_suggestions_alter'] = 'test_module';

    $container = new ContainerBuilder();
    $container->setParameter('container.modules', $module_filenames);
    (new HookCollectorPass())->process($container);
    // Ensure that the hook is registered for the module it resides in.
    // Even though there is a more specific match the current module takes
    // precedence.
    $this->assertEquals(
      ['test_module_theme_suggestions_alter' => 'test_module'],
      $container->getParameter('.hook_data')['hook_list']['theme_suggestions_alter'],
    );
  }

}
