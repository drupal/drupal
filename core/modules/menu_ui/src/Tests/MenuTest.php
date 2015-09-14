<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Tests\MenuTest.
 */

namespace Drupal\menu_ui\Tests;

use Drupal\block\Entity\Block;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;
use Drupal\node\Entity\Node;

/**
 * Add a custom menu, add menu links to the custom menu and Tools menu, check
 * their data, and delete them using the UI.
 *
 * @group menu_ui
 */
class MenuTest extends MenuWebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'block', 'contextual', 'help', 'path', 'test_page_test');

  /**
   * A user with administration rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticatedUser;

  /**
   * Array of placed menu blocks keyed by block ID.
   *
   * @var array
   */
  protected $blockPlacements;

  /**
   * A test menu.
   *
   * @var \Drupal\system\Entity\Menu
   */
  protected $menu;

  /**
   * An array of test menu links.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentInterface[]
   */
  protected $items;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    // Create users.
    $this->adminUser = $this->drupalCreateUser(array('access administration pages', 'administer blocks', 'administer menu', 'create article content'));
    $this->authenticatedUser = $this->drupalCreateUser(array());
  }

  /**
   * Tests menu functionality using the admin and user interfaces.
   */
  function testMenu() {
    // Login the user.
    $this->drupalLogin($this->adminUser);
    $this->items = array();

    $this->menu = $this->addCustomMenu();
    $this->doMenuTests();
    $this->doTestMenuBlock();
    $this->addInvalidMenuLink();
    $this->addCustomMenuCRUD();

    // Verify that the menu links rebuild is idempotent and leaves the same
    // number of links in the table.
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $before_count = $menu_link_manager->countMenuLinks(NULL);
    $menu_link_manager->rebuild();
    $after_count = $menu_link_manager->countMenuLinks(NULL);
    $this->assertIdentical($before_count, $after_count, 'MenuLinkManager::rebuild() does not add more links');
    // Do standard user tests.
    // Login the user.
    $this->drupalLogin($this->authenticatedUser);
    $this->verifyAccess(403);

    foreach ($this->items as $item) {
      // Menu link URIs are stored as 'internal:/node/$nid'.
      $node = Node::load(str_replace('internal:/node/', '', $item->link->uri));
      $this->verifyMenuLink($item, $node);
    }

    // Login the administrator.
    $this->drupalLogin($this->adminUser);

    // Verify delete link exists and reset link does not exist.
    $this->drupalGet('admin/structure/menu/manage/' . $this->menu->id());
    $this->assertLinkByHref(Url::fromRoute('entity.menu_link_content.delete_form',  ['menu_link_content' => $this->items[0]->id()])->toString());
    $this->assertNoLinkByHref(Url::fromRoute('menu_ui.link_reset', ['menu_link_plugin' => $this->items[0]->getPluginId()])->toString());
    // Check delete and reset access.
    $this->drupalGet('admin/structure/menu/item/' . $this->items[0]->id() . '/delete');
    $this->assertResponse(200);
    $this->drupalGet('admin/structure/menu/link/' . $this->items[0]->getPluginId() . '/reset');
    $this->assertResponse(403);

    // Delete menu links.
    foreach ($this->items as $item) {
      $this->deleteMenuLink($item);
    }

    // Delete custom menu.
    $this->deleteCustomMenu();

    // Modify and reset a standard menu link.
    $instance = $this->getStandardMenuLink();
    $old_weight = $instance->getWeight();
    // Edit the static menu link.
    $edit = array();
    $edit['weight'] = 10;
    $id = $instance->getPluginId();
    $this->drupalPostForm("admin/structure/menu/link/$id/edit", $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText('The menu link has been saved.');
    $menu_link_manager->resetDefinitions();

    $instance = $menu_link_manager->createInstance($instance->getPluginId());
    $this->assertEqual($edit['weight'], $instance->getWeight(), 'Saving an existing link updates the weight.');
    $this->resetMenuLink($instance, $old_weight);
  }

  /**
   * Adds a custom menu using CRUD functions.
   */
  function addCustomMenuCRUD() {
    // Add a new custom menu.
    $menu_name = substr(hash('sha256', $this->randomMachineName(16)), 0, MENU_MAX_MENU_NAME_LENGTH_UI);
    $label = $this->randomMachineName(16);

    $menu = entity_create('menu', array(
      'id' => $menu_name,
      'label' => $label,
      'description' => 'Description text',
    ));
    $menu->save();

    // Assert the new menu.
    $this->drupalGet('admin/structure/menu/manage/' . $menu_name);
    $this->assertRaw($label, 'Custom menu was added.');

    // Edit the menu.
    $new_label = $this->randomMachineName(16);
    $menu->set('label', $new_label);
    $menu->save();
    $this->drupalGet('admin/structure/menu/manage/' . $menu_name);
    $this->assertRaw($new_label, 'Custom menu was edited.');
  }

  /**
   * Creates a custom menu.
   *
   * @return \Drupal\system\Entity\Menu
   *   The custom menu that has been created.
   */
  function addCustomMenu() {
    // Try adding a menu using a menu_name that is too long.
    $this->drupalGet('admin/structure/menu/add');
    $menu_name = substr(hash('sha256', $this->randomMachineName(16)), 0, MENU_MAX_MENU_NAME_LENGTH_UI + 1);
    $label = $this->randomMachineName(16);
    $edit = array(
      'id' => $menu_name,
      'description' => '',
      'label' =>  $label,
    );
    $this->drupalPostForm('admin/structure/menu/add', $edit, t('Save'));

    // Verify that using a menu_name that is too long results in a validation
    // message.
    $this->assertRaw(t('@name cannot be longer than %max characters but is currently %length characters long.', array(
      '@name' => t('Menu name'),
      '%max' => MENU_MAX_MENU_NAME_LENGTH_UI,
      '%length' => Unicode::strlen($menu_name),
    )));

    // Change the menu_name so it no longer exceeds the maximum length.
    $menu_name = substr(hash('sha256', $this->randomMachineName(16)), 0, MENU_MAX_MENU_NAME_LENGTH_UI);
    $edit['id'] = $menu_name;
    $this->drupalPostForm('admin/structure/menu/add', $edit, t('Save'));

    // Verify that no validation error is given for menu_name length.
    $this->assertNoRaw(t('@name cannot be longer than %max characters but is currently %length characters long.', array(
      '@name' => t('Menu name'),
      '%max' => MENU_MAX_MENU_NAME_LENGTH_UI,
      '%length' => Unicode::strlen($menu_name),
    )));
    // Verify that the confirmation message is displayed.
    $this->assertRaw(t('Menu %label has been added.', array('%label' => $label)));
    $this->drupalGet('admin/structure/menu');
    $this->assertText($label, 'Menu created');

    // Confirm that the custom menu block is available.
    $this->drupalGet('admin/structure/block/list/' . $this->config('system.theme')->get('default'));
    $this->clickLinkPartialName('Place block');
    $this->assertText($label);

    // Enable the block.
    $block = $this->drupalPlaceBlock('system_menu_block:' . $menu_name);
    $this->blockPlacements[$menu_name] = $block->id();
    return Menu::load($menu_name);
  }

  /**
   * Deletes the locally stored custom menu.
   *
   * This deletes the custom menu that is stored in $this->menu and performs
   * tests on the menu delete user interface.
   */
  function deleteCustomMenu() {
    $menu_name = $this->menu->id();
    $label = $this->menu->label();

    // Delete custom menu.
    $this->drupalPostForm("admin/structure/menu/manage/$menu_name/delete", array(), t('Delete'));
    $this->assertResponse(200);
    $this->assertRaw(t('The menu %title has been deleted.', array('%title' => $label)), 'Custom menu was deleted');
    $this->assertNull(Menu::load($menu_name), 'Custom menu was deleted');
    // Test if all menu links associated with the menu were removed from
    // database.
    $result = entity_load_multiple_by_properties('menu_link_content', array('menu_name' => $menu_name));
    $this->assertFalse($result, 'All menu links associated with the custom menu were deleted.');

    // Make sure there's no delete button on system menus.
    $this->drupalGet('admin/structure/menu/manage/main');
    $this->assertNoRaw('edit-delete', 'The delete button was not found');

    // Try to delete the main menu.
    $this->drupalGet('admin/structure/menu/manage/main/delete');
    $this->assertText(t('You are not authorized to access this page.'));
  }

  /**
   * Tests menu functionality.
   */
  function doMenuTests() {
    $menu_name = $this->menu->id();

    // Test the 'Add link' local action.
    $this->drupalGet(Url::fromRoute('entity.menu.edit_form',  ['menu' => $menu_name]));

    $this->clickLink(t('Add link'));
    $link_title = $this->randomString();
    $this->drupalPostForm(NULL, array('link[0][uri]' => '/', 'title[0][value]' => $link_title), t('Save'));
    $this->assertUrl(Url::fromRoute('entity.menu.edit_form',  ['menu' => $menu_name]));
    // Test the 'Edit' operation.
    $this->clickLink(t('Edit'));
    $this->assertFieldByName('title[0][value]', $link_title);
    $link_title = $this->randomString();
    $this->drupalPostForm(NULL, array('title[0][value]' => $link_title), t('Save'));
    $this->assertUrl(Url::fromRoute('entity.menu.edit_form',  ['menu' => $menu_name]));
    // Test the 'Delete' operation.
    $this->clickLink(t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the custom menu link %item?', array('%item' => $link_title)));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertUrl(Url::fromRoute('entity.menu.edit_form',  ['menu' => $menu_name]));

    // Add nodes to use as links for menu links.
    $node1 = $this->drupalCreateNode(array('type' => 'article'));
    $node2 = $this->drupalCreateNode(array('type' => 'article'));
    $node3 = $this->drupalCreateNode(array('type' => 'article'));
    $node4 = $this->drupalCreateNode(array('type' => 'article'));
    // Create a node with an alias.
    $node5 = $this->drupalCreateNode(array(
      'type' => 'article',
      'path' => array(
        'alias' => '/node5',
      ),
    ));

    // Verify add link button.
    $this->drupalGet('admin/structure/menu');
    $this->assertLinkByHref('admin/structure/menu/manage/' . $menu_name . '/add', 0, "The add menu link button url is correct");

    // Verify form defaults.
    $this->doMenuLinkFormDefaultsTest();

    // Add menu links.
    $item1 = $this->addMenuLink('', '/node/' . $node1->id(), $menu_name, TRUE);
    $item2 = $this->addMenuLink($item1->getPluginId(), '/node/' . $node2->id(), $menu_name, FALSE);
    $item3 = $this->addMenuLink($item2->getPluginId(), '/node/' . $node3->id(), $menu_name);

    // Hierarchy
    // <$menu_name>
    // - item1
    // -- item2
    // --- item3

    $this->assertMenuLink($item1->getPluginId(), array(
      'children' => array($item2->getPluginId(), $item3->getPluginId()),
      'parents' => array($item1->getPluginId()),
      // We assert the language code here to make sure that the language
      // selection element degrades gracefully without the Language module.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item2->getPluginId(), array(
      'children' => array($item3->getPluginId()),
      'parents' => array($item2->getPluginId(), $item1->getPluginId()),
      // See above.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item3->getPluginId(), array(
      'children' => array(),
      'parents' => array($item3->getPluginId(), $item2->getPluginId(), $item1->getPluginId()),
      // See above.
      'langcode' => 'en',
    ));

    // Verify menu links.
    $this->verifyMenuLink($item1, $node1);
    $this->verifyMenuLink($item2, $node2, $item1, $node1);
    $this->verifyMenuLink($item3, $node3, $item2, $node2);

    // Add more menu links.
    $item4 = $this->addMenuLink('', '/node/' . $node4->id(), $menu_name);
    $item5 = $this->addMenuLink($item4->getPluginId(), '/node/' . $node5->id(), $menu_name);
    // Create a menu link pointing to an alias.
    $item6 = $this->addMenuLink($item4->getPluginId(), '/node5', $menu_name, TRUE, '0');

    // Hierarchy
    // <$menu_name>
    // - item1
    // -- item2
    // --- item3
    // - item4
    // -- item5
    // -- item6

    $this->assertMenuLink($item4->getPluginId(), array(
      'children' => array($item5->getPluginId(), $item6->getPluginId()),
      'parents' => array($item4->getPluginId()),
      // See above.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item5->getPluginId(), array(
      'children' => array(),
      'parents' => array($item5->getPluginId(), $item4->getPluginId()),
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item6->getPluginId(), array(
      'children' => array(),
      'parents' => array($item6->getPluginId(), $item4->getPluginId()),
      'route_name' => 'entity.node.canonical',
      'route_parameters' => array('node' => $node5->id()),
      'url' => '',
      // See above.
      'langcode' => 'en',
    ));

    // Modify menu links.
    $this->modifyMenuLink($item1);
    $this->modifyMenuLink($item2);

    // Toggle menu links.
    $this->toggleMenuLink($item1);
    $this->toggleMenuLink($item2);

    // Move link and verify that descendants are updated.
    $this->moveMenuLink($item2, $item5->getPluginId(), $menu_name);
    // Hierarchy
    // <$menu_name>
    // - item1
    // - item4
    // -- item5
    // --- item2
    // ---- item3
    // -- item6

    $this->assertMenuLink($item1->getPluginId(), array(
      'children' => array(),
      'parents' => array($item1->getPluginId()),
      // See above.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item4->getPluginId(), array(
      'children' => array($item5->getPluginId(), $item6->getPluginId(), $item2->getPluginId(), $item3->getPluginId()),
      'parents' => array($item4->getPluginId()),
      // See above.
      'langcode' => 'en',
    ));

    $this->assertMenuLink($item5->getPluginId(), array(
      'children' => array($item2->getPluginId(), $item3->getPluginId()),
      'parents' => array($item5->getPluginId(), $item4->getPluginId()),
      // See above.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item2->getPluginId(), array(
      'children' => array($item3->getPluginId()),
      'parents' => array($item2->getPluginId(), $item5->getPluginId(), $item4->getPluginId()),
      // See above.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item3->getPluginId(), array(
      'children' => array(),
      'parents' => array($item3->getPluginId(), $item2->getPluginId(), $item5->getPluginId(), $item4->getPluginId()),
      // See above.
      'langcode' => 'en',
    ));

    // Add 102 menu links with increasing weights, then make sure the last-added
    // item's weight doesn't get changed because of the old hardcoded delta=50.
    $items = array();
    for ($i = -50; $i <= 51; $i++) {
      $items[$i] = $this->addMenuLink('', '/node/' . $node1->id(), $menu_name, TRUE, strval($i));
    }
    $this->assertMenuLink($items[51]->getPluginId(), array('weight' => '51'));

    // Disable a link and then re-enable the link via the overview form.
    $this->disableMenuLink($item1);
    $edit = array();
    $edit['links[menu_plugin_id:' . $item1->getPluginId() . '][enabled]'] = TRUE;
    $this->drupalPostForm('admin/structure/menu/manage/' . $item1->getMenuName(), $edit, t('Save'));

    // Mark item2, item4 and item5 as expanded.
    // This is done in order to show them on the frontpage.
    $item2->expanded->value = 1;
    $item2->save();
    $item4->expanded->value = 1;
    $item4->save();
    $item5->expanded->value = 1;
    $item5->save();

    // Verify in the database.
    $this->assertMenuLink($item1->getPluginId(), array('enabled' => 1));

    // Add an external link.
    $item7 = $this->addMenuLink('', 'https://www.drupal.org', $menu_name);
    $this->assertMenuLink($item7->getPluginId(), array('url' => 'https://www.drupal.org'));

    // Add <front> menu item.
    $item8 = $this->addMenuLink('', '/', $menu_name);
    $this->assertMenuLink($item8->getPluginId(), array('route_name' => '<front>'));
    $this->drupalGet('');
    $this->assertResponse(200);
    // Make sure we get routed correctly.
    $this->clickLink($item8->getTitle());
    $this->assertResponse(200);

    // Check invalid menu link parents.
    $this->checkInvalidParentMenuLinks();

    // Save menu links for later tests.
    $this->items[] = $item1;
    $this->items[] = $item2;
  }

  /**
   * Ensures that the proper default values are set when adding a menu link
   */
  protected function doMenuLinkFormDefaultsTest() {
    $this->drupalGet("admin/structure/menu/manage/tools/add");
    $this->assertResponse(200);

    $this->assertFieldByName('title[0][value]', '');
    $this->assertFieldByName('link[0][uri]', '');

    $this->assertNoFieldChecked('edit-expanded-value');
    $this->assertFieldChecked('edit-enabled-value');

    $this->assertFieldByName('description[0][value]', '');
    $this->assertFieldByName('weight[0][value]', 0);
  }

  /**
   * Adds and removes a menu link with a query string and fragment.
   */
  function testMenuQueryAndFragment() {
    $this->drupalLogin($this->adminUser);

    // Make a path with query and fragment on.
    $path = '/test-page?arg1=value1&arg2=value2';
    $item = $this->addMenuLink('', $path);

    $this->drupalGet('admin/structure/menu/item/' . $item->id() . '/edit');
    $this->assertFieldByName('link[0][uri]', $path, 'Path is found with both query and fragment.');

    // Now change the path to something without query and fragment.
    $path = '/test-page';
    $this->drupalPostForm('admin/structure/menu/item/' . $item->id() . '/edit', array('link[0][uri]' => $path), t('Save'));
    $this->drupalGet('admin/structure/menu/item/' . $item->id() . '/edit');
    $this->assertFieldByName('link[0][uri]', $path, 'Path no longer has query or fragment.');

    // Use <front>#fragment and ensure that saving it does not lose its content.
    $path = '<front>?arg1=value#fragment';
    $item = $this->addMenuLink('', $path);

    $this->drupalGet('admin/structure/menu/item/' . $item->id() . '/edit');
    $this->assertFieldByName('link[0][uri]', $path, 'Path is found with both query and fragment.');

    $this->drupalPostForm('admin/structure/menu/item/' . $item->id() . '/edit', array(), t('Save'));

    $this->drupalGet('admin/structure/menu/item/' . $item->id() . '/edit');
    $this->assertFieldByName('link[0][uri]', $path, 'Path is found with both query and fragment.');
  }

  /**
   * Tests renaming the built-in menu.
   */
  function testSystemMenuRename() {
    $this->drupalLogin($this->adminUser);
    $edit = array(
      'label' => $this->randomMachineName(16),
    );
    $this->drupalPostForm('admin/structure/menu/manage/main', $edit, t('Save'));

    // Make sure menu shows up with new name in block addition.
    $default_theme = $this->config('system.theme')->get('default');
    $this->drupalget('admin/structure/block/list/' . $default_theme);
    $this->clickLinkPartialName('Place block');
    $this->assertText($edit['label']);
  }

  /**
   * Tests that menu items pointing to unpublished nodes are editable.
   */
  function testUnpublishedNodeMenuItem() {
    $this->drupalLogin($this->drupalCreateUser(array('access administration pages', 'administer blocks', 'administer menu', 'create article content', 'bypass node access')));
    // Create an unpublished node.
    $node = $this->drupalCreateNode(array(
      'type' => 'article',
      'status' => NODE_NOT_PUBLISHED,
    ));

    $item = $this->addMenuLink('', '/node/' . $node->id());
    $this->modifyMenuLink($item);

    // Test that a user with 'administer menu' but without 'bypass node access'
    // cannot see the menu item.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/menu/manage/' . $item->getMenuName());
    $this->assertNoText($item->getTitle(), "Menu link pointing to unpublished node is only visible to users with 'bypass node access' permission");
    // The cache contexts associated with the (in)accessible menu links are
    // bubbled. See DefaultMenuLinkTreeManipulators::menuLinkCheckAccess().
    $this->assertCacheContext('user.permissions');
  }

  /**
   * Tests the contextual links on a menu block.
   */
  public function testBlockContextualLinks() {
    $this->drupalLogin($this->drupalCreateUser(array('administer menu', 'access contextual links', 'administer blocks')));
    $custom_menu = $this->addCustomMenu();
    $this->addMenuLink('', '/', $custom_menu->id());
    $block = $this->drupalPlaceBlock('system_menu_block:' . $custom_menu->id(), array('label' => 'Custom menu', 'provider' => 'system'));
    $this->drupalGet('test-page');

    $id = 'block:block=' . $block->id() . ':langcode=en|menu:menu=' . $custom_menu->id() . ':langcode=en';
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:assertContextualLinkPlaceHolder()
    $this->assertRaw('<div data-contextual-id="'. $id . '"></div>', format_string('Contextual link placeholder with id @id exists.', array('@id' => $id)));

    // Get server-rendered contextual links.
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:renderContextualLinks()
    $post = array('ids[0]' => $id);
    $response =  $this->drupalPost('contextual/render', 'application/json', $post, array('query' => array('destination' => 'test-page')));
    $this->assertResponse(200);
    $json = Json::decode($response);
    $this->assertIdentical($json[$id], '<ul class="contextual-links"><li class="block-configure"><a href="' . base_path() . 'admin/structure/block/manage/' . $block->id() . '">Configure block</a></li><li class="entitymenuedit-form"><a href="' . base_path() . 'admin/structure/menu/manage/' . $custom_menu->id() . '">Edit menu</a></li></ul>');
  }

  /**
   * Adds a menu link using the UI.
   *
   * @param string $parent
   *   Optional parent menu link id.
   * @param string $path
   *   The path to enter on the form. Defaults to the front page.
   * @param string $menu_name
   *   Menu name. Defaults to 'tools'.
   * @param bool $expanded
   *   Whether or not this menu link is expanded. Setting this to TRUE should
   *   test whether it works when we do the authenticatedUser tests. Defaults
   *   to FALSE.
   * @param string $weight
   *  Menu weight. Defaults to 0.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   A menu link entity.
   */
  function addMenuLink($parent = '', $path = '/', $menu_name = 'tools', $expanded = FALSE, $weight = '0') {
    // View add menu link page.
    $this->drupalGet("admin/structure/menu/manage/$menu_name/add");
    $this->assertResponse(200);

    $title = '!link_' . $this->randomMachineName(16);
    $edit = array(
      'link[0][uri]' => $path,
      'title[0][value]' => $title,
      'description[0][value]' => '',
      'enabled[value]' => 1,
      'expanded[value]' => $expanded,
      'menu_parent' =>  $menu_name . ':' . $parent,
      'weight[0][value]' => $weight,
    );

    // Add menu link.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText('The menu link has been saved.');

    $menu_links = entity_load_multiple_by_properties('menu_link_content', array('title' => $title));

    $menu_link = reset($menu_links);
    $this->assertTrue($menu_link, 'Menu link was found in database.');
    $this->assertMenuLink($menu_link->getPluginId(), array('menu_name' => $menu_name, 'children' => array(), 'parent' => $parent));

    return $menu_link;
  }

  /**
   * Attempts to add menu link with invalid path or no access permission.
   */
  function addInvalidMenuLink() {
    foreach (array('access' => '/admin/people/permissions') as $type => $link_path) {
      $edit = array(
        'link[0][uri]' => $link_path,
        'title[0][value]' => 'title',
      );
      $this->drupalPostForm("admin/structure/menu/manage/{$this->menu->id()}/add", $edit, t('Save'));
      $this->assertRaw(t("The path '@link_path' is inaccessible.", array('@link_path' => $link_path)), 'Menu link was not created');
    }
  }

  /**
   * Tests that parent options are limited by depth when adding menu links.
   */
  function checkInvalidParentMenuLinks() {
    $last_link = null;
    $created_links = array();

    // Get the max depth of the tree.
    $menu_link_tree = \Drupal::service('menu.link_tree');
    $max_depth = $menu_link_tree->maxDepth();

    // Create a maximum number of menu links, each a child of the previous.
    for ($i = 0; $i <= $max_depth - 1; $i++) {
      $parent = $last_link ? 'tools:' . $last_link->getPluginId() : 'tools:';
      $title = 'title' . $i;
      $edit = array(
        'link[0][uri]' => '/',
        'title[0][value]' => $title,
        'menu_parent' => $parent,
        'description[0][value]' => '',
        'enabled[value]' => 1,
        'expanded[value]' => FALSE,
        'weight[0][value]' => '0',
      );
      $this->drupalPostForm("admin/structure/menu/manage/{$this->menu->id()}/add", $edit, t('Save'));
      $menu_links = entity_load_multiple_by_properties('menu_link_content', array('title' => $title));
      $last_link = reset($menu_links);
      $created_links[]  = 'tools:' . $last_link->getPluginId();
    }

    // The last link cannot be a parent in the new menu link form.
    $this->drupalGet('admin/structure/menu/manage/admin/add');
    $value = 'tools:' . $last_link->getPluginId();
    $this->assertNoOption('edit-menu-parent', $value, 'The invalid option is not there.');

    // All but the last link can be parents in the new menu link form.
    array_pop($created_links);
    foreach ($created_links as $key => $link) {
      $this->assertOption('edit-menu-parent', $link, 'The valid option number ' . ($key + 1) . ' is there.');
    }
  }

  /**
   * Verifies a menu link using the UI.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $item
   *   Menu link.
   * @param object $item_node
   *   Menu link content node.
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $parent
   *   Parent menu link.
   * @param object $parent_node
   *   Parent menu link content node.
   */
  function verifyMenuLink(MenuLinkContent $item, $item_node, MenuLinkContent $parent = NULL, $parent_node = NULL) {
    // View home page.
    $this->drupalGet('');
    $this->assertResponse(200);

    // Verify parent menu link.
    if (isset($parent)) {
      // Verify menu link.
      $title = $parent->getTitle();
      $this->assertLink($title, 0, 'Parent menu link was displayed');

      // Verify menu link link.
      $this->clickLink($title);
      $title = $parent_node->label();
      $this->assertTitle(t("@title | Drupal", array('@title' => $title)), 'Parent menu link link target was correct');
    }

    // Verify menu link.
    $title = $item->getTitle();
    $this->assertLink($title, 0, 'Menu link was displayed');

    // Verify menu link link.
    $this->clickLink($title);
    $title = $item_node->label();
    $this->assertTitle(t("@title | Drupal", array('@title' => $title)), 'Menu link link target was correct');
  }

  /**
   * Changes the parent of a menu link using the UI.
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $item
   *   The menu link item to move.
   * @param int $parent
   *   The id of the new parent.
   * @param string $menu_name
   *   The menu the menu link will be moved to.
   */
  function moveMenuLink(MenuLinkContent $item, $parent, $menu_name) {
    $mlid = $item->id();

    $edit = array(
      'menu_parent' => $menu_name . ':' . $parent,
    );
    $this->drupalPostForm("admin/structure/menu/item/$mlid/edit", $edit, t('Save'));
    $this->assertResponse(200);
  }

  /**
   * Modifies a menu link using the UI.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $item
   *   Menu link entity.
   */
  function modifyMenuLink(MenuLinkContent $item) {
    $item->title->value = $this->randomMachineName(16);

    $mlid = $item->id();
    $title = $item->getTitle();

    // Edit menu link.
    $edit = array();
    $edit['title[0][value]'] = $title;
    $this->drupalPostForm("admin/structure/menu/item/$mlid/edit", $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText('The menu link has been saved.');
    // Verify menu link.
    $this->drupalGet('admin/structure/menu/manage/' . $item->getMenuName());
    $this->assertText($title, 'Menu link was edited');
  }

  /**
   * Resets a standard menu link using the UI.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $menu_link
   *   The Menu link.
   * @param int $old_weight
   *   Original title for menu link.
   */
  function resetMenuLink(MenuLinkInterface $menu_link, $old_weight) {
    // Reset menu link.
    $this->drupalPostForm("admin/structure/menu/link/{$menu_link->getPluginId()}/reset", array(), t('Reset'));
    $this->assertResponse(200);
    $this->assertRaw(t('The menu link was reset to its default settings.'), 'Menu link was reset');

    // Verify menu link.
    $instance = \Drupal::service('plugin.manager.menu.link')->createInstance($menu_link->getPluginId());
    $this->assertEqual($old_weight, $instance->getWeight(), 'Resets to the old weight.');
  }

  /**
   * Deletes a menu link using the UI.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $item
   *   Menu link.
   */
  function deleteMenuLink(MenuLinkContent $item) {
    $mlid = $item->id();
    $title = $item->getTitle();

    // Delete menu link.
    $this->drupalPostForm("admin/structure/menu/item/$mlid/delete", array(), t('Delete'));
    $this->assertResponse(200);
    $this->assertRaw(t('The menu link %title has been deleted.', array('%title' => $title)), 'Menu link was deleted');

    // Verify deletion.
    $this->drupalGet('');
    $this->assertNoText($title, 'Menu link was deleted');
  }

  /**
   * Alternately disables and enables a menu link.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $item
   *   Menu link.
   */
  function toggleMenuLink(MenuLinkContent $item) {
    $this->disableMenuLink($item);

    // Verify menu link is absent.
    $this->drupalGet('');
    $this->assertNoText($item->getTitle(), 'Menu link was not displayed');
    $this->enableMenuLink($item);

    // Verify menu link is displayed.
    $this->drupalGet('');
    $this->assertText($item->getTitle(), 'Menu link was displayed');
  }

  /**
   * Disables a menu link.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $item
   *   Menu link.
   */
  function disableMenuLink(MenuLinkContent $item) {
    $mlid = $item->id();
    $edit['enabled[value]'] = FALSE;
    $this->drupalPostForm("admin/structure/menu/item/$mlid/edit", $edit, t('Save'));

    // Unlike most other modules, there is no confirmation message displayed.
    // Verify in the database.
    $this->assertMenuLink($item->getPluginId(), array('enabled' => 0));
  }

  /**
   * Enables a menu link.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $item
   *   Menu link.
   */
  function enableMenuLink(MenuLinkContent $item) {
    $mlid = $item->id();
    $edit['enabled[value]'] = TRUE;
    $this->drupalPostForm("admin/structure/menu/item/$mlid/edit", $edit, t('Save'));

    // Verify in the database.
    $this->assertMenuLink($item->getPluginId(), array('enabled' => 1));
  }

  /**
   * Tests if administrative users other than user 1 can access the menu parents
   * AJAX callback.
   */
  public function testMenuParentsJsAccess() {
    $admin = $this->drupalCreateUser(array('administer menu'));
    $this->drupalLogin($admin);
    // Just check access to the callback overall, the POST data is irrelevant.
    $this->drupalGetAjax('admin/structure/menu/parents');
    $this->assertResponse(200);

    // Do standard user tests.
    // Login the user.
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGetAjax('admin/structure/menu/parents');
    $this->assertResponse(403);
  }

  /**
   * Returns standard menu link.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface
   *   A menu link plugin.
   */
  private function getStandardMenuLink() {
    // Retrieve menu link id of the Log out menu link, which will always be on
    // the front page.
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $result = $menu_link_manager->loadLinksByRoute('user.logout');
    $instance = reset($result);

    $this->assertTrue((bool) $instance, 'Standard menu link was loaded');
    return $instance;
  }

  /**
   * Verifies the logged in user has the desired access to various menu pages.
   *
   * @param integer $response
   *   The expected HTTP response code. Defaults to 200.
   */
  private function verifyAccess($response = 200) {
    // View menu help page.
    $this->drupalGet('admin/help/menu');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText(t('Menu'), 'Menu help was displayed');
    }

    // View menu build overview page.
    $this->drupalGet('admin/structure/menu');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText(t('Menus'), 'Menu build overview page was displayed');
    }

    // View tools menu customization page.
    $this->drupalGet('admin/structure/menu/manage/' . $this->menu->id());
        $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText(t('Tools'), 'Tools menu page was displayed');
    }

    // View menu edit page for a static link.
    $item = $this->getStandardMenuLink();
    $this->drupalGet('admin/structure/menu/link/' . $item->getPluginId() . '/edit');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText(t('Edit menu item'), 'Menu edit page was displayed');
    }

    // View add menu page.
    $this->drupalGet('admin/structure/menu/add');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText(t('Menus'), 'Add menu page was displayed');
    }
  }

  /**
   * Tests menu block settings.
   */
  protected function doTestMenuBlock() {
    $menu_id = $this->menu->id();
    $block_id = $this->blockPlacements[$menu_id];
    $this->drupalGet('admin/structure/block/manage/' . $block_id);
    $this->drupalPostForm(NULL, [
      'settings[depth]' => 3,
      'settings[level]' => 2,
    ], t('Save block'));
    $block = Block::load($block_id);
    $settings = $block->getPlugin()->getConfiguration();
    $this->assertEqual($settings['depth'], 3);
    $this->assertEqual($settings['level'], 2);
    // Reset settings.
    $block->getPlugin()->setConfigurationValue('depth', 0);
    $block->getPlugin()->setConfigurationValue('level', 1);
    $block->save();
  }

}
