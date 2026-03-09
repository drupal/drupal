<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Layout;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Layout\LayoutPluginManager exceptions.
 *
 * These tests were separated from LayoutPluginManagerTest because they would
 * not execute properly due to all the expectations added in its setUp().
 */
#[Group('Layout')]
class LayoutPluginManagerExceptionTest extends UnitTestCase {

  /**
   * Tests ::processDefinition() with a layout that doesn't have a label.
   *
   * @legacy-covers ::processDefinition
   */
  public function testProcessDefinitionWithMissingLayoutLabel(): void {
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('The "test_layout" layout definition must have a label.');
    $definition = new LayoutDefinition([
      'id' => 'a_label_less_layout',
      'class' => LayoutDefault::class,
    ]);
    $layoutPluginManager = new LayoutPluginManager(
      new \ArrayObject(['Drupal\Core']),
      $this->createStub(CacheBackendInterface::class),
      $this->createStub(ModuleHandlerInterface::class),
      $this->createStub(ThemeHandlerInterface::class),
    );
    $layoutPluginManager->processDefinition($definition, 'test_layout');
  }

}
