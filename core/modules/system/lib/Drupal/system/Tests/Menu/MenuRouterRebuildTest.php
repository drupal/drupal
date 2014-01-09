<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Menu\MenuRouterRebuildTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\Language;

/**
 * Tests menu_router_rebuild().
 */
class MenuRouterRebuildTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale', 'menu_test');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Menu router rebuild',
      'description' => 'Tests menu_router_rebuild().',
      'group' => 'Menu',
    );
  }

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $language = new Language(array('id' => 'nl'));
    language_save($language);
  }

  /**
   * Tests configuration context when rebuilding the menu router table.
   */
  public function testMenuRouterRebuildContext() {
    // Enter a language context before rebuilding the menu router tables.
    \Drupal::configFactory()->setLanguage(language_load('nl'));
    menu_router_rebuild();

    // Check that the language context was not used for building the menu item.
    $menu_item = menu_get_item('menu-test/context');
    $this->assertTrue($menu_item['title'] == 'English', 'Config context overrides are ignored when rebuilding menu router items.');
  }

}
