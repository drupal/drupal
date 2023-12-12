<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\system\Entity\Menu;
use Drupal\system\MenuStorage;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use Drupal\Tests\menu_ui\Traits\MenuUiTrait;

/**
 * Tests custom menu and menu links operations using the UI.
 *
 * @group menu_ui
 */
class MenuUiJavascriptTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;
  use MenuUiTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'contextual',
    'menu_link_content',
    'menu_ui',
    'test_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the contextual links on a menu block.
   */
  public function testBlockContextualLinks() {
    $this->drupalLogin($this->drupalCreateUser([
      'administer menu',
      'access contextual links',
      'administer blocks',
    ]));
    $menu = $this->addCustomMenu();

    $block = $this->drupalPlaceBlock('system_menu_block:' . $menu->id(), [
      'label' => 'Custom menu',
      'provider' => 'system',
    ]);
    $this->addMenuLink('', '/', $menu->id());

    $this->drupalGet('test-page');

    // Click on 'Configure block' contextual link.
    $this->clickContextualLink("#block-{$block->id()}", 'Configure block');
    // Check that we're on block configuration form.
    $this->assertNotEmpty($this->getSession()->getPage()->findLink('Remove block'));

    $this->drupalGet('test-page');

    // Click on 'Edit menu' contextual link.
    $this->clickContextualLink("#block-{$block->id()}", 'Edit menu');
    // Check that we're on block configuration form.
    $this->assertSession()->pageTextContains("Machine name: {$menu->id()}");
  }

  /**
   * Creates a custom menu.
   *
   * @return \Drupal\system\Entity\Menu
   *   The custom menu that has been created.
   */
  protected function addCustomMenu() {
    // Try adding a menu using a menu_name that is too long.
    $label = $this->randomMachineName(16);
    $menu_id = $this->randomMachineName(MenuStorage::MAX_ID_LENGTH + 1);

    $this->drupalGet('admin/structure/menu/add');
    $page = $this->getSession()->getPage();
    // Type the label to activate the machine name field and the edit button.
    $page->fillField('Title', $label);
    // Wait for the machine name widget to be activated.
    $this->assertSession()->waitForElementVisible('css', 'button[type=button].link:contains(Edit)');
    // Activate the machine name text field.
    $page->pressButton('Edit');
    // Try to fill a text longer than the allowed limit.
    $page->fillField('Menu name', $menu_id);
    $page->pressButton('Save');
    // Check that the menu was saved with the ID truncated to the max length.
    $menu = Menu::load(substr($menu_id, 0, MenuStorage::MAX_ID_LENGTH));
    $this->assertEquals($label, $menu->label());

    // Check that the menu was added.
    $this->drupalGet('admin/structure/menu');
    $this->assertSession()->pageTextContains($label);

    // Confirm that the custom menu block is available.
    $this->drupalGet('admin/structure/block/list/' . $this->config('system.theme')->get('default'));
    $this->clickLink('Place block');
    // Wait for the modal dialog to be loaded.
    $this->assertSession()->waitForElement('css', "div[aria-describedby=drupal-modal]");
    // Check that the block is available to be placed.
    $this->assertSession()->pageTextContains($label);

    return $menu;
  }

  /**
   * Adds a menu link using the UI.
   *
   * @param string $parent
   *   Optional parent menu link id.
   * @param string $path
   *   The path to enter on the form. Defaults to the front page.
   * @param string $menu_id
   *   Menu ID. Defaults to 'tools'.
   * @param bool $expanded
   *   Whether or not this menu link is expanded. Setting this to TRUE should
   *   test whether it works when we do the authenticatedUser tests. Defaults
   *   to FALSE.
   * @param string $weight
   *   Menu weight. Defaults to 0.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   A menu link entity.
   */
  protected function addMenuLink($parent = '', $path = '/', $menu_id = 'tools', $expanded = FALSE, $weight = '0') {
    // View add menu link page.
    $this->drupalGet("admin/structure/menu/manage/$menu_id/add");

    $title = '!link_' . $this->randomMachineName(16);
    $edit = [
      'link[0][uri]' => $path,
      'title[0][value]' => $title,
      'description[0][value]' => '',
      'enabled[value]' => 1,
      'expanded[value]' => $expanded,
      'menu_parent' => $menu_id . ':' . $parent,
      'weight[0][value]' => $weight,
    ];

    // Add menu link.
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The menu link has been saved.');

    $storage = $this->container->get('entity_type.manager')->getStorage('menu_link_content');
    $menu_links = $storage->loadByProperties(['title' => $title]);
    $menu_link = reset($menu_links);

    // Check that the stored menu link meeting the expectations.
    $this->assertNotNull($menu_link);
    $this->assertMenuLink([
      'menu_name' => $menu_id,
      'children' => [],
      'parent' => $parent,
    ], $menu_link->getPluginId());

    return $menu_link;
  }

}
