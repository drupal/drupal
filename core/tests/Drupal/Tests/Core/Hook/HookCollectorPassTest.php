<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Hook;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ProceduralCall;
use Drupal\Core\Hook\HookCollectorBase;
use Drupal\Core\Hook\HookCollectorPass;
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

  /**
   * Tests collect all hook implementations.
   *
   * @legacy-covers ::collectAllHookImplementations
   * @legacy-covers ::getHookFileIterator
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
    file_put_contents('vfs://drupal_root/modules/test_module/test_module.module', <<<__EOF__
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
  }

  public function testCollectsOnlyRootProceduralAndHookDirectoryFiles(): void {
    vfsStream::setup('drupal_root');
    vfsStream::create([
      'test_module' => [
        'test_module.module' => '',
        'test_module.install' => '',
        'test_module.profile' => '',
        'another_module.module' => '',
        'ignored.php' => '',
        'src' => [
          'Hook' => [
            'TestHooks.php' => '',
            'Subdirectory' => ['NestedHooks.php' => ''],
          ],
          'Other' => ['IgnoredHooks.php' => ''],
        ],
        'ignored_directory' => ['ignored.module' => ''],
      ],
    ]);

    $collector = new HookCollectorPass();
    $get_files = \Closure::bind(
      static function (HookCollectorPass $collector, string $directory, string $module): array {
        $procedural_files = [
          "$module.module",
          "$module.profile",
          "$module.install",
        ];
        return iterator_to_array($collector->getHookFileIterator($directory, $procedural_files));
      },
      NULL,
      HookCollectorBase::class,
    );
    $files = $get_files($collector, 'vfs://drupal_root/test_module', 'test_module');
    $paths = array_map(static fn (\SplFileInfo $file): string => $file->getPathname(), $files);
    sort($paths);

    $this->assertSame([
      'vfs://drupal_root/test_module/src/Hook/Subdirectory/NestedHooks.php',
      'vfs://drupal_root/test_module/src/Hook/TestHooks.php',
      'vfs://drupal_root/test_module/test_module.install',
      'vfs://drupal_root/test_module/test_module.module',
      'vfs://drupal_root/test_module/test_module.profile',
    ], $paths);
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
