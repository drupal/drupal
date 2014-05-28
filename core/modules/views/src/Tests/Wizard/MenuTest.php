<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Wizard\MenuTest.
 */

namespace Drupal\views\Tests\Wizard;

/**
 * Tests the ability of the views wizard to put views in a menu.
 */
class MenuTest extends WizardTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Menu functionality',
      'description' => 'Test the ability of the views wizard to put views in a menu.',
      'group' => 'Views Wizard',
    );
  }

  /**
   * Tests the menu functionality.
   */
  function testMenus() {
    // Create a view with a page display and a menu link in the Main Menu.
    $view = array();
    $view['label'] = $this->randomName(16);
    $view['id'] = strtolower($this->randomName(16));
    $view['description'] = $this->randomName(16);
    $view['page[create]'] = 1;
    $view['page[title]'] = $this->randomName(16);
    $view['page[path]'] = $this->randomName(16);
    $view['page[link]'] = 1;
    $view['page[link_properties][menu_name]'] = 'main';
    $view['page[link_properties][title]'] = $this->randomName(16);
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));

    // Make sure there is a link to the view from the front page (where we
    // expect the main menu to display).
    $this->drupalGet('');
    $this->assertResponse(200);
    $this->assertLink($view['page[link_properties][title]']);
    $this->assertLinkByHref(url($view['page[path]']));

    // Make sure the link is associated with the main menu.
    $links = menu_load_links('main');
    $found = FALSE;
    foreach ($links as $link) {
      if ($link['link_path'] == $view['page[path]']) {
        $found = TRUE;
        break;
      }
    }
    $this->assertTrue($found, t('Found a link to %path in the main menu', array('%path' => $view['page[path]'])));
  }

}
