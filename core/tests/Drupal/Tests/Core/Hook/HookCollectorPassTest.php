<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Hook;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ProceduralCall;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\HookCollectorPass;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\Core\GroupIncludesTestTrait;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @coversDefaultClass \Drupal\Core\Hook\HookCollectorPass
 * @group Hook
 */
class HookCollectorPassTest extends UnitTestCase {

  use GroupIncludesTestTrait;

  /**
   * @covers ::collectAllHookImplementations
   * @covers ::filterIterator
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
    $implementations['test_hook'][ProceduralCall::class]['test_module_test_hook'] = 'test_module';
    $includes = [
      'test_module_test_hook' => 'vfs://drupal_root/modules/test_module/includes/test_module.inc',
    ];

    $container = new ContainerBuilder();
    $container->setParameter('container.modules', $module_filenames);
    $container->setDefinition('module_handler', new Definition());
    (new HookCollectorPass())->process($container);
    $this->assertSame($implementations, $container->getParameter('hook_implementations_map'));
    $this->assertSame($includes, $container->getDefinition(ProceduralCall::class)->getArguments()[0]);
  }

  /**
   * @covers ::process
   * @covers ::collectModuleHookImplementations
   */
  public function testGroupIncludes(): void {
    $module_filenames = self::setupGroupIncludes();
    $container = new ContainerBuilder();
    $container->setParameter('container.modules', $module_filenames);
    $container->setDefinition('module_handler', new Definition());
    (new HookCollectorPass())->process($container);
    $argument = $container->getDefinition('module_handler')->getArgument('$groupIncludes');
    $this->assertSame(self::GROUP_INCLUDES, $argument);
  }

  /**
   * @covers ::getHookAttributesInClass
   */
  public function testGetHookAttributesInClass(): void {
    /** @phpstan-ignore-next-line */
    $getHookAttributesInClass = fn ($class) => $this->getHookAttributesInClass($class);
    $p = new HookCollectorPass();
    $getHookAttributesInClass = $getHookAttributesInClass->bindTo($p, $p);
    $x = new class {

      #[Hook('install')]
      function foo(): void {}

    };
    $this->expectException(\LogicException::class);
    $hooks = $getHookAttributesInClass(get_class($x));
    $x = new class {

      #[Hook('foo')]
      function foo(): void {}

    };
    $hooks = $getHookAttributesInClass(get_class($x));
    $hook = reset($hooks);
    $this->assertInstanceOf(Hook::class, $hook);
    $this->assertSame('foo', $hook->hook);
  }

}
