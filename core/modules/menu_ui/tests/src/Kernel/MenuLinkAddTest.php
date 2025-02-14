<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_ui\Kernel;

use Drupal\Core\Menu\LocalActionWithDestination;
use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests \Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd deprecation.
 *
 * @group menu_ui
 * @group legacy
 */
class MenuLinkAddTest extends KernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_ui',
    'menu_link_add_test',
  ];

  /**
   * Tests \Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd deprecation.
   */
  public function testDeprecation(): void {
    /** @var \Drupal\Core\Menu\LocalActionManagerInterface $local_action_manager */
    $local_action_manager = $this->container->get('plugin.manager.menu.local_action');

    $this->expectDeprecation('Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Menu\LocalActionWithDestination instead. See https://www.drupal.org/node/3490245');
    $instance = $local_action_manager->createInstance('entity.menu.add_link_form_deprecated');
    $this->assertInstanceOf(MenuLinkAdd::class, $instance);
  }

  /**
   * Tests deprecated plugin does not trigger deprecation unless used.
   */
  public function testNoDeprecation(): void {
    /** @var \Drupal\Core\Menu\LocalActionManagerInterface $local_action_manager */
    $local_action_manager = $this->container->get('plugin.manager.menu.local_action');

    $instance = $local_action_manager->createInstance('entity.menu.add_link_form');
    $this->assertInstanceOf(LocalActionWithDestination::class, $instance);

    $deprecated_definition = $local_action_manager->getDefinition('entity.menu.add_link_form_deprecated');
    $this->assertSame(MenuLinkAdd::class, $deprecated_definition['class']);
  }

}
