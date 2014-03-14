<?php

/**
 * @file
 * Contains \Drupal\menu\Tests\MenuCacheTagsTest.
 */

namespace Drupal\menu\Tests;

use Drupal\system\Tests\Cache\PageCacheTagsTestBase;

/**
 * Tests the Menu and Menu Link entities' cache tags.
 */
class MenuCacheTagsTest extends PageCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('menu', 'block', 'test_page_test');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "Menu & Menu link entities cache tags",
      'description' => "Test the Menu & Menu link entities' cache tags.",
      'group' => 'Menu',
    );
  }

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
    $menu_link = entity_create('menu_link', array(
      'link_path' => '<front>',
      'link_title' => 'Vicuña',
      'menu_name' => 'llama',
    ));
    $menu_link->save();
    $block = $this->drupalPlaceBlock('system_menu_block:llama', array('label' => 'Llama', 'module' => 'system', 'region' => 'footer'));

    // Prime the page cache.
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->verifyPageCache($path, 'HIT', array('content:1', 'menu:llama'));


    // Verify that after modifying the menu, there is a cache miss.
    $this->pass('Test modification of menu.', 'Debug');
    $menu->label = 'Awesome llama';
    $menu->save();
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($path, 'HIT');


    // Verify that after modifying the menu link, there is a cache miss.
    $this->pass('Test modification of menu link.', 'Debug');
    $menu_link->link_title = 'Guanaco';
    $menu_link->save();
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($path, 'HIT');


    // Verify that after adding a menu link, there is a cache miss.
    $this->pass('Test addition of menu link.', 'Debug');
    $menu_link_2 = entity_create('menu_link', array(
      'link_path' => '<front>',
      'link_title' => 'Alpaca',
      'menu_name' => 'llama',
    ));
    $menu_link_2->save();
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($path, 'HIT');


    // Verify that after deleting the first menu link, there is a cache miss.
    $this->pass('Test deletion of menu link.', 'Debug');
    $menu_link->delete();
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($path, 'HIT', array('content:1', 'menu:llama'));


    // Verify that after deleting the menu, there is a cache miss.
    $this->pass('Test deletion of menu.', 'Debug');
    $menu->delete();
    $this->verifyPageCache($path, 'MISS');

    // Verify a cache hit.
    $this->verifyPageCache($path, 'HIT', array('content:1'));
  }

}
