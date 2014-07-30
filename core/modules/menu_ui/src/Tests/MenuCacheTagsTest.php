<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Tests\MenuCacheTagsTest.
 */

namespace Drupal\menu_ui\Tests;

use Drupal\system\Tests\Cache\PageCacheTagsTestBase;

/**
 * Tests the Menu and Menu Link entities' cache tags.
 *
 * @group menu_ui
 */
class MenuCacheTagsTest extends PageCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('menu_ui', 'block', 'test_page_test');

  /**
   * Tests cache tags presence and invalidation of the Menu entity.
   *
   * Tests the following cache tags:
   * - "menu:<menu ID>"
   */
  public function testMenuBlock() {
    $path = 'test-page';

    // Create a Llama menu, add a link to it and place the corresponding block.
    $menu = entity_create('menu', array(
      'id' => 'llama',
      'label' => 'Llama',
      'description' => 'Description text',
    ));
    $menu->save();
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    // Move a link into the new menu.
    $menu_link = $menu_link_manager->updateDefinition('test_page_test.test_page', array('menu_name' => 'llama', 'parent' => ''));
    $block = $this->drupalPlaceBlock('system_menu_block:llama', array('label' => 'Llama', 'provider' => 'system', 'region' => 'footer'));

    // Prime the page cache.
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $expected_tags = array(
      'theme:stark',
      'theme_global_settings:1',
      'rendered:1',
      'block_view:1',
      'block:' . $block->id(),
      'block_plugin:system_menu_block__llama',
      'menu:llama',
    );
    $this->verifyPageCache($path, 'HIT', $expected_tags);

    // Verify that after modifying the menu, there is a cache miss.
    $this->pass('Test modification of menu.', 'Debug');
    $menu->label = 'Awesome llama';
    $menu->save();
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($path, 'HIT');

    // Verify that after modifying the menu link weight, there is a cache miss.
    $menu_link_manager->updateDefinition('test_page_test.test_page', array('weight' => -10));
    $this->pass('Test modification of menu link.', 'Debug');
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($path, 'HIT');

    // Verify that after adding a menu link, there is a cache miss.
    $this->pass('Test addition of menu link.', 'Debug');
    $menu_link_2 = entity_create('menu_link_content', array(
      'id' => '',
      'parent' => '',
      'title' => 'Alpaca',
      'menu_name' => 'llama',
      'route_name' => '<front>',
      'bundle' => 'menu_name',
    ));
    $menu_link_2->save();
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($path, 'HIT');

    // Verify that after resetting the first menu link, there is a cache miss.
    $this->pass('Test reset of menu link.', 'Debug');
    $this->assertTrue($menu_link->isResettable(), 'First link can be reset');
    $menu_link = $menu_link_manager->resetLink($menu_link->getPluginId());
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($path, 'HIT', $expected_tags);

    // Verify that after deleting the menu, there is a cache miss.
    $this->pass('Test deletion of menu.', 'Debug');
    $menu->delete();
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($path, 'HIT', array('rendered:1', 'theme:stark', 'theme_global_settings:1'));
  }

}
