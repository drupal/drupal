<?php

namespace Drupal\Tests\system\Kernel;

use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\Entity\PathAlias;
use Drupal\KernelTests\KernelTestBase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Path\Entity\PathAlias
 *
 * @group path
 */
class PathHooksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('path_alias');
  }

  /**
   * Tests that the PathAlias entity clears caches correctly.
   *
   * @covers ::postSave
   * @covers ::postDelete
   */
  public function testPathHooks() {
    $path_alias = PathAlias::create([
      'path' => '/' . $this->randomMachineName(),
      'alias' => '/' . $this->randomMachineName(),
    ]);

    // Check \Drupal\Core\Path\Entity\PathAlias::postSave() for new path alias
    // entities.
    $alias_manager = $this->prophesize(AliasManagerInterface::class);
    $alias_manager->cacheClear(Argument::any())->shouldBeCalledTimes(1);
    $alias_manager->cacheClear($path_alias->getPath())->shouldBeCalledTimes(1);
    \Drupal::getContainer()->set('path.alias_manager', $alias_manager->reveal());
    $path_alias->save();

    $new_source = '/' . $this->randomMachineName();

    // Check \Drupal\Core\Path\Entity\PathAlias::postSave() for existing path
    // alias entities.
    $alias_manager = $this->prophesize(AliasManagerInterface::class);
    $alias_manager->cacheClear(Argument::any())->shouldBeCalledTimes(2);
    $alias_manager->cacheClear($path_alias->getPath())->shouldBeCalledTimes(1);
    $alias_manager->cacheClear($new_source)->shouldBeCalledTimes(1);
    \Drupal::getContainer()->set('path.alias_manager', $alias_manager->reveal());
    $path_alias->setPath($new_source);
    $path_alias->save();

    // Check \Drupal\Core\Path\Entity\PathAlias::postDelete().
    $alias_manager = $this->prophesize(AliasManagerInterface::class);
    $alias_manager->cacheClear(Argument::any())->shouldBeCalledTimes(1);
    $alias_manager->cacheClear($new_source)->shouldBeCalledTimes(1);
    \Drupal::getContainer()->set('path.alias_manager', $alias_manager->reveal());
    $path_alias->delete();
  }

}
