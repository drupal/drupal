<?php

namespace Drupal\KernelTests\Core\Menu;

use Drupal\KernelTests\KernelTestBase;

/**
 * Deprecation tests cases for the menu layer.
 *
 * @group legacy
 */
class MenuLegacyTest extends KernelTestBase {

  /**
   * Tests deprecation of the menu_local_tabs() function.
   *
   * @expectedDeprecation menu_local_tabs() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use local_tasks_block block or inline theming instead. See https://www.drupal.org/node/2874695
   */
  public function testLocalTabs() {
    $this->assertSame([], menu_local_tabs());
  }

  /**
   * Tests deprecation of the menu_primary_local_tasks() function.
   *
   * @expectedDeprecation menu_primary_local_tasks() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Menu\LocalTaskManagerInterface::getLocalTasks() instead. See https://www.drupal.org/node/2874695
   */
  public function testPrimaryLocalTasks() {
    $this->assertSame('', menu_primary_local_tasks());
  }

  /**
   * Tests deprecation of the menu_secondary_local_tasks() function.
   *
   * @expectedDeprecation menu_secondary_local_tasks() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Menu\LocalTaskManagerInterface::getLocalTasks() instead. See https://www.drupal.org/node/2874695
   */
  public function testSecondaryLocalTasks() {
    $this->assertSame('', menu_secondary_local_tasks());
  }

  /**
   * Tests deprecation of the menu_cache_clear_all() function.
   *
   * @expectedDeprecation menu_cache_clear_all() is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0. Use \Drupal::cache('menu')->invalidateAll() instead. See https://www.drupal.org/node/2989138
   */
  public function testMenuCacheClearAll() {
    $cache = \Drupal::cache('menu');
    $cache->set('test_cache', 'test_data');
    menu_cache_clear_all();
    $this->assertFalse($cache->get('test_cache'));
  }

}
