<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Wizard\MenuTest.
 */

namespace Drupal\views\Tests\Wizard;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;

/**
 * Tests the ability of the views wizard to put views in a menu.
 *
 * @group views
 */
class MenuTest extends WizardTestBase {

  /**
   * Tests the menu functionality.
   */
  function testMenus() {
    $this->drupalPlaceBlock('system_menu_block:main');

    // Create a view with a page display and a menu link in the Main Menu.
    $view = array();
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
    $view['description'] = $this->randomMachineName(16);
    $view['page[create]'] = 1;
    $view['page[title]'] = $this->randomMachineName(16);
    $view['page[path]'] = $this->randomMachineName(16);
    $view['page[link]'] = 1;
    $view['page[link_properties][menu_name]'] = 'main';
    $view['page[link_properties][title]'] = $this->randomMachineName(16);
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));

    // Make sure there is a link to the view from the front page (where we
    // expect the main menu to display).
    $this->drupalGet('');
    $this->assertResponse(200);
    $this->assertLink($view['page[link_properties][title]']);
    $this->assertLinkByHref(Url::fromUri('base:' . $view['page[path]'])->toString());

    // Make sure the link is associated with the main menu.
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
    $link = $menu_link_manager->createInstance('views_view:views.' . $view['id'] . '.page_1');
    $url = $link->getUrlObject();
    $this->assertEqual($url->getRouteName(), 'view.' . $view['id'] . '.page_1', SafeMarkup::format('Found a link to %path in the main menu', array('%path' => $view['page[path]'])));
    $metadata = $link->getMetaData();
    $this->assertEqual(array('view_id' => $view['id'], 'display_id' => 'page_1'), $metadata);
  }

}
