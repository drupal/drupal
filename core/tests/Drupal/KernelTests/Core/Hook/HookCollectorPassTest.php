<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Hook;

use Drupal\Core\Hook\HookCollectorPass;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @coversDefaultClass \Drupal\Core\Hook\HookCollectorPass
 * @group Hook
 */
class HookCollectorPassTest extends KernelTestBase {

  /**
   * VFS does not and can not support symlinks.
   */
  protected function setUpFilesystem(): void {}

  /**
   * Test that symlinks are properly followed.
   */
  public function testSymlink(): void {
    mkdir($this->siteDirectory);

    foreach (scandir("core/modules/user/tests/modules/user_hooks_test") as $item) {
      $target = "$this->siteDirectory/$item";
      if (!file_exists($target)) {
        symlink(realpath("core/modules/user/tests/modules/user_hooks_test/$item"), $target);
      }
    }
    $container = new ContainerBuilder();
    $module_filenames = [
      'user_hooks_test' => ['pathname' => "$this->siteDirectory/user_hooks_test.info.yml"],
    ];
    $container->setParameter('container.modules', $module_filenames);
    $container->setDefinition('module_handler', new Definition());
    (new HookCollectorPass())->process($container);
    $implementations = [
      'user_format_name_alter' => [
        'Drupal\user_hooks_test\Hook\UserHooksTest' => [
          'userFormatNameAlter' => 'user_hooks_test',
        ],
      ],
    ];

    $this->assertSame($implementations, $container->getParameter('hook_implementations_map'));
  }

}
