<?php

namespace Drupal\Tests\block\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests block local tasks.
 *
 * @group block
 */
class BlockLocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->directoryList = ['block' => 'core/modules/block'];
    parent::setUp();

    $config_factory = $this->getConfigFactoryStub([
      'system.theme' => ['default' => 'test_c'],
    ]);

    $themes = [];
    $themes['test_a'] = (object) [
      'status' => 1,
      'info' => [
        'name' => 'test_a',
        'hidden' => TRUE,
      ],
    ];
    $themes['test_b'] = (object) [
      'status' => 1,
      'info' => [
        'name' => 'test_b',
      ],
    ];
    $themes['test_c'] = (object) [
      'status' => 1,
      'info' => [
        'name' => 'test_c',
      ],
    ];
    $theme_handler = $this->createMock('Drupal\Core\Extension\ThemeHandlerInterface');
    $theme_handler->expects($this->any())
      ->method('listInfo')
      ->willReturn($themes);
    $theme_handler->expects($this->any())
      ->method('hasUi')
      ->willReturnMap([
        ['test_a', FALSE],
        ['test_b', TRUE],
        ['test_c', TRUE],
      ]);

    $container = new ContainerBuilder();
    $container->set('config.factory', $config_factory);
    $container->set('theme_handler', $theme_handler);
    $container->setParameter('app.root', $this->root);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the admin edit local task.
   */
  public function testBlockAdminLocalTasks() {
    $this->assertLocalTasks('entity.block.edit_form', [['entity.block.edit_form']]);
  }

  /**
   * Tests the block admin display local tasks.
   *
   * @dataProvider providerTestBlockAdminDisplay
   */
  public function testBlockAdminDisplay($route, $expected) {
    $this->assertLocalTasks($route, $expected);
  }

  /**
   * Provides a list of routes to test.
   */
  public function providerTestBlockAdminDisplay() {
    return [
      ['block.admin_display', [['block.admin_display'], ['block.admin_display_theme:test_b', 'block.admin_display_theme:test_c']]],
      ['block.admin_display_theme', [['block.admin_display'], ['block.admin_display_theme:test_b', 'block.admin_display_theme:test_c']]],
    ];
  }

}
