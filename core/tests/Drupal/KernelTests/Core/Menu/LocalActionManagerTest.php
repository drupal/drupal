<?php

namespace Drupal\KernelTests\Core\Menu;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the local action manager.
 *
 * @coversDefaultClass \Drupal\Core\Menu\LocalActionManager
 * @group Menu
 */
class LocalActionManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_test', 'user', 'system'];

  /**
   * Tests the cacheability of local actions.
   */
  public function testCacheability() {
    /** @var \Drupal\Core\Menu\LocalActionManager $local_action_manager */
    $local_action_manager = \Drupal::service('plugin.manager.menu.local_action');
    $build = [
      '#cache' => [
        'key' => 'foo',
      ],
      $local_action_manager->getActionsForRoute('menu_test.local_action7'),
    ];

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $renderer->renderRoot($build);

    $this->assertContains('menu_local_action7', $build[0]['menu_test.local_action7']['#cache']['tags']);
    $this->assertContains('url.query_args:menu_local_action7', $build[0]['menu_test.local_action7']['#cache']['contexts']);

    $this->assertContains('menu_local_action8', $build[0]['menu_test.local_action8']['#cache']['tags']);
    $this->assertContains('url.query_args:menu_local_action8', $build[0]['menu_test.local_action8']['#cache']['contexts']);

    $this->assertContains('menu_local_action7', $build['#cache']['tags']);
    $this->assertContains('url.query_args:menu_local_action7', $build['#cache']['contexts']);

    $this->assertContains('menu_local_action8', $build['#cache']['tags']);
    $this->assertContains('url.query_args:menu_local_action8', $build['#cache']['contexts']);
  }

}
