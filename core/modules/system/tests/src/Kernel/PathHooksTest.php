<?php

namespace Drupal\Tests\system\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Prophecy\Argument;

/**
 * @group Drupal
 */
class PathHooksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  static public $modules = ['system'];

  /**
   * Test system_path_*() correctly clears caches.
   */
  public function testPathHooks() {
    $source = '/' . $this->randomMachineName();
    $alias = '/' . $this->randomMachineName();

    // Check system_path_insert();
    $alias_manager = $this->prophesize(AliasManagerInterface::class);
    $alias_manager->cacheClear(Argument::any())->shouldBeCalledTimes(1);
    $alias_manager->cacheClear($source)->shouldBeCalledTimes(1);
    \Drupal::getContainer()->set('path.alias_manager', $alias_manager->reveal());
    $alias_storage = \Drupal::service('path.alias_storage');
    $alias_storage->save($source, $alias);

    $new_source = '/' . $this->randomMachineName();
    $path = $alias_storage->load(['source' => $source]);

    // Check system_path_update();
    $alias_manager = $this->prophesize(AliasManagerInterface::class);
    $alias_manager->cacheClear(Argument::any())->shouldBeCalledTimes(2);
    $alias_manager->cacheClear($source)->shouldBeCalledTimes(1);
    $alias_manager->cacheClear($new_source)->shouldBeCalledTimes(1);
    \Drupal::getContainer()->set('path.alias_manager', $alias_manager->reveal());
    $alias_storage->save($new_source, $alias, LanguageInterface::LANGCODE_NOT_SPECIFIED, $path['pid']);

    // Check system_path_delete();
    $alias_manager = $this->prophesize(AliasManagerInterface::class);
    $alias_manager->cacheClear(Argument::any())->shouldBeCalledTimes(1);
    $alias_manager->cacheClear($new_source)->shouldBeCalledTimes(1);
    \Drupal::getContainer()->set('path.alias_manager', $alias_manager->reveal());
    $alias_storage->delete(['pid' => $path['pid']]);

  }

}
