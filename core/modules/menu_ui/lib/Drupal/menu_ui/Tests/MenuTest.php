<?php

/**
 * @file
 * Definition of Drupal\menu_ui\Tests\MenuTest.
 */

namespace Drupal\menu_ui\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\system\Entity\Menu;

/**
 * Defines a test class for testing menu and menu link functionality.
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
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $authenticated_user;

  /**
   * A test menu.
   *
   * @var \Drupal\system\Entity\Menu
   */
  protected $menu;

  /**
   * An array of test menu links.
   *
   * @var array
   */
  protected $items;

  public static function getInfo() {
    return array(
      'name' => 'Menu link creation/deletion',
      'description' => 'Add a custom menu, add menu links to the custom menu and Tools menu, check their data, and delete them using the UI.',
      'group' => 'Menu'
    );
  }

  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    // Create users.
    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'administer blocks', 'administer menu', 'create article content'));
    $this->authenticated_user = $this->drupalCreateUser(array());
  }

  /**
   * Tests menu functionality using the admin and user interfaces.
   */
  function testMenu() {
    // Login the user.
    $this->drupalLogin($this->admin_user);
    $this->items = array();

    $this->menu = $this->addCustomMenu();
    $this->doMenuTests();
    $this->addInvalidMenuLink();
    $this->addCustomMenuCRUD();

    // Verify that the menu links rebuild is idempotent and leaves the same
    // number of links in the table.
    $before_count = db_query('SELECT COUNT(*) FROM {menu_links}')->fetchField();
    menu_link_rebuild_defaults();
    $after_count = db_query('SELECT COUNT(*) FROM {menu_links}')->fetchField();
    $this->assertIdentical($before_count, $after_count, 'menu_link_rebuild_defaults() does not add more links');
    // Do standard user tests.
    // Login the user.
    $this->drupalLogin($this->authenticated_user);
    $this->verifyAccess(403);
    foreach ($this->items as $item) {
      // Paths were set as 'node/$nid'.
      $node = node_load(substr($item['link_path'], 5));
      $this->verifyMenuLink($item, $node);
    }

    // Login the administrator.
    $this->drupalLogin($this->admin_user);

    // Delete menu links.
    foreach ($this->items as $item) {
      $this->deleteMenuLink($item);
    }

    // Delete custom menu.
    $this->deleteCustomMenu();

    // Modify and reset a standard menu link.
    $item = $this->getStandardMenuLink();
    $old_title = $item['link_title'];
    $this->modifyMenuLink($item);
    $item = entity_load('menu_link', $item['mlid']);
    // Verify that a change to the description is saved.
    $description = $this->randomName(16);
    $item['options']['attributes']['title']  = $description;
    $return_value = menu_link_save($item);
    // Save the menu link again to test the return value of the procedural save
    // helper.
    $this->assertIdentical($return_value, $item->save(), 'Return value of menu_link_save() is identical to the return value of $menu_link->save().');
    $saved_item = entity_load('menu_link', $item['mlid']);
    $this->assertEqual($description, $saved_item['options']['attributes']['title'], 'Saving an existing link updates the description (title attribute)');
    $this->resetMenuLink($item, $old_title);
  }

  /**
   * Adds a custom menu using CRUD functions.
   */
  function addCustomMenuCRUD() {
    // Add a new custom menu.
    $menu_name = substr(hash('sha256', $this->randomName(16)), 0, MENU_MAX_MENU_NAME_LENGTH_UI);
    $label = $this->randomName(16);

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
    $new_label = $this->randomName(16);
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
    $menu_name = substr(hash('sha256', $this->randomName(16)), 0, MENU_MAX_MENU_NAME_LENGTH_UI + 1);
    $label = $this->randomName(16);
    $edit = array(
      'id' => $menu_name,
      'description' => '',
      'label' =>  $label,
    );
    $this->drupalPostForm('admin/structure/menu/add', $edit, t('Save'));

    // Verify that using a menu_name that is too long results in a validation
    // message.
    $this->assertRaw(t('!name cannot be longer than %max characters but is currently %length characters long.', array(
      '!name' => t('Menu name'),
      '%max' => MENU_MAX_MENU_NAME_LENGTH_UI,
      '%length' => drupal_strlen($menu_name),
    )));

    // Change the menu_name so it no longer exceeds the maximum length.
    $menu_name = substr(hash('sha256', $this->randomName(16)), 0, MENU_MAX_MENU_NAME_LENGTH_UI);
    $edit['id'] = $menu_name;
    $this->drupalPostForm('admin/structure/menu/add', $edit, t('Save'));

    // Verify that no validation error is given for menu_name length.
    $this->assertNoRaw(t('!name cannot be longer than %max characters but is currently %length characters long.', array(
      '!name' => t('Menu name'),
      '%max' => MENU_MAX_MENU_NAME_LENGTH_UI,
      '%length' => drupal_strlen($menu_name),
    )));
    // Verify that the confirmation message is displayed.
    $this->assertRaw(t('Menu %label has been added.', array('%label' => $label)));
    $this->drupalGet('admin/structure/menu');
    $this->assertText($label, 'Menu created');

    // Confirm that the custom menu block is available.
    $this->drupalGet('admin/structure/block/list/' . \Drupal::config('system.theme')->get('default'));
    $this->assertText($label);

    // Enable the block.
    $this->drupalPlaceBlock('system_menu_block:' . $menu_name);
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
    $this->assertRaw(t('The custom menu %title has been deleted.', array('%title' => $label)), 'Custom menu was deleted');
    $this->assertNull(Menu::load($menu_name), 'Custom menu was deleted');
    // Test if all menu links associated to the menu were removed from database.
    $result = entity_load_multiple_by_properties('menu_link', array('menu_name' => $menu_name));
    $this->assertFalse($result, 'All menu links associated to the custom menu were deleted.');

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
    // Add nodes to use as links for menu links.
    $node1 = $this->drupalCreateNode(array('type' => 'article'));
    $node2 = $this->drupalCreateNode(array('type' => 'article'));
    $node3 = $this->drupalCreateNode(array('type' => 'article'));
    $node4 = $this->drupalCreateNode(array('type' => 'article'));
    // Create a node with an alias.
    $node5 = $this->drupalCreateNode(array(
      'type' => 'article',
      'path' => array(
        'alias' => 'node5',
      ),
    ));

    // Add menu links.
    $item1 = $this->addMenuLink(0, 'node/' . $node1->id(), $menu_name);
    $item2 = $this->addMenuLink($item1['mlid'], 'node/' . $node2->id(), $menu_name, FALSE);
    $item3 = $this->addMenuLink($item2['mlid'], 'node/' . $node3->id(), $menu_name);
    $this->assertMenuLink($item1['mlid'], array(
      'depth' => 1,
      'has_children' => 1,
      'p1' => $item1['mlid'],
      'p2' => 0,
      // We assert the language code here to make sure that the language
      // selection element degrades gracefully without the Language module.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item2['mlid'], array(
      'depth' => 2, 'has_children' => 1,
      'p1' => $item1['mlid'],
      'p2' => $item2['mlid'],
      'p3' => 0,
      // See above.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item3['mlid'], array(
      'depth' => 3,
      'has_children' => 0,
      'p1' => $item1['mlid'],
      'p2' => $item2['mlid'],
      'p3' => $item3['mlid'],
      'p4' => 0,
      // See above.
      'langcode' => 'en',
    ));

    // Verify menu links.
    $this->verifyMenuLink($item1, $node1);
    $this->verifyMenuLink($item2, $node2, $item1, $node1);
    $this->verifyMenuLink($item3, $node3, $item2, $node2);

    // Add more menu links.
    $item4 = $this->addMenuLink(0, 'node/' . $node4->id(), $menu_name);
    $item5 = $this->addMenuLink($item4['mlid'], 'node/' . $node5->id(), $menu_name);
    // Create a menu link pointing to an alias.
    $item6 = $this->addMenuLink($item4['mlid'], 'node5', $menu_name, TRUE, '0', 'node/' . $node5->id());
    $this->assertMenuLink($item4['mlid'], array(
      'depth' => 1,
      'has_children' => 1,
      'p1' => $item4['mlid'],
      'p2' => 0,
      // See above.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item5['mlid'], array(
      'depth' => 2,
      'has_children' => 0,
      'p1' => $item4['mlid'],
      'p2' => $item5['mlid'],
      'p3' => 0,
      // See above.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item6['mlid'], array(
      'depth' => 2,
      'has_children' => 0,
      'p1' => $item4['mlid'],
      'p2' => $item6['mlid'],
      'p3' => 0,
      'link_path' => 'node/' . $node5->id(),
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
    $this->moveMenuLink($item2, $item5['mlid'], $menu_name);
    $this->assertMenuLink($item1['mlid'], array(
      'depth' => 1,
      'has_children' => 0,
      'p1' => $item1['mlid'],
      'p2' => 0,
      // See above.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item4['mlid'], array(
      'depth' => 1,
      'has_children' => 1,
      'p1' => $item4['mlid'],
      'p2' => 0,
      // See above.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item5['mlid'], array(
      'depth' => 2,
      'has_children' => 1,
      'p1' => $item4['mlid'],
      'p2' => $item5['mlid'],
      'p3' => 0,
      // See above.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item2['mlid'], array(
      'depth' => 3,
      'has_children' => 1,
      'p1' => $item4['mlid'],
      'p2' => $item5['mlid'],
      'p3' => $item2['mlid'],
      'p4' => 0,
      // See above.
      'langcode' => 'en',
    ));
    $this->assertMenuLink($item3['mlid'], array(
      'depth' => 4,
      'has_children' => 0,
      'p1' => $item4['mlid'],
      'p2' => $item5['mlid'],
      'p3' => $item2['mlid'],
      'p4' => $item3['mlid'],
      'p5' => 0,
      // See above.
      'langcode' => 'en',
    ));

    // Add 102 menu links with increasing weights, then make sure the last-added
    // item's weight doesn't get changed because of the old hardcoded delta=50.
    $items = array();
    for ($i = -50; $i <= 51; $i++) {
      $items[$i] = $this->addMenuLink(0, 'node/' . $node1->id(), $menu_name, TRUE, strval($i));
    }
    $this->assertMenuLink($items[51]['mlid'], array('weight' => '51'));

    // Enable a link via the overview form.
    $this->disableMenuLink($item1);
    $edit = array();

    // Note in the UI the 'links[mlid:x][hidden]' form element maps to enabled,
    // or NOT hidden.
    $edit['links[mlid:' . $item1['mlid'] . '][hidden]'] = TRUE;
    $this->drupalPostForm('admin/structure/menu/manage/' . $item1['menu_name'], $edit, t('Save'));

    // Verify in the database.
    $this->assertMenuLink($item1['mlid'], array('hidden' => 0));

    // Add an external link.
    $item7 = $this->addMenuLink(0, 'http://drupal.org', $menu_name);
    $this->assertMenuLink($item7['mlid'], array('link_path' => 'http://drupal.org', 'external' => 1));

    // Add <front> menu item.
    $item8 = $this->addMenuLink(0, '<front>', $menu_name);
    $this->assertMenuLink($item8['mlid'], array('link_path' => '<front>', 'external' => 1));
    $this->drupalGet('');
    $this->assertResponse(200);
    // Make sure we get routed correctly.
    $this->clickLink($item8['link_title']);
    $this->assertResponse(200);

    // Save menu links for later tests.
    $this->items[] = $item1;
    $this->items[] = $item2;
  }

  /**
   * Adds and removes a menu link with a query string and fragment.
   */
  function testMenuQueryAndFragment() {
    $this->drupalLogin($this->admin_user);

    // Make a path with query and fragment on.
    $path = 'test-page?arg1=value1&arg2=value2';
    $item = $this->addMenuLink(0, $path);

    $this->drupalGet('admin/structure/menu/item/' . $item['mlid'] . '/edit');
    $this->assertFieldByName('link_path', $path, 'Path is found with both query and fragment.');

    // Now change the path to something without query and fragment.
    $path = 'test-page';
    $this->drupalPostForm('admin/structure/menu/item/' . $item['mlid'] . '/edit', array('link_path' => $path), t('Save'));
    $this->drupalGet('admin/structure/menu/item/' . $item['mlid'] . '/edit');
    $this->assertFieldByName('link_path', $path, 'Path no longer has query or fragment.');
  }

  /**
   * Tests renaming the built-in menu.
   */
  function testSystemMenuRename() {
    $this->drupalLogin($this->admin_user);
    $edit = array(
      'label' => $this->randomName(16),
    );
    $this->drupalPostForm('admin/structure/menu/manage/main', $edit, t('Save'));

    // Make sure menu shows up with new name in block addition.
    $default_theme = \Drupal::config('system.theme')->get('default');
    $this->drupalget('admin/structure/block/list/' . $default_theme);
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

    $item = $this->addMenuLink(0, 'node/' . $node->id());
    $this->modifyMenuLink($item);

    // Test that a user with 'administer menu' but without 'bypass node access'
    // cannot see the menu item.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/structure/menu/manage/' . $item['menu_name']);
    $this->assertNoText($item['link_title'], "Menu link pointing to unpublished node is only visible to users with 'bypass node access' permission");
  }

  /**
   * Tests the contextual links on a menu block.
   */
  public function testBlockContextualLinks() {
    $this->drupalLogin($this->drupalCreateUser(array('administer menu', 'access contextual links', 'administer blocks')));
    $this->addMenuLink();
    $block = $this->drupalPlaceBlock('system_menu_block:tools', array('label' => 'Tools', 'provider' => 'system'));
    $this->drupalGet('test-page');

    $id = 'block:block=' . $block->id() . ':|menu:menu=tools:';
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:assertContextualLinkPlaceHolder()
    $this->assertRaw('<div data-contextual-id="'. $id . '"></div>', format_string('Contextual link placeholder with id @id exists.', array('@id' => $id)));

    // Get server-rendered contextual links.
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:renderContextualLinks()
    $post = array('ids[0]' => $id);
    $response =  $this->drupalPost('contextual/render', 'application/json', $post, array('query' => array('destination' => 'test-page')));
    $this->assertResponse(200);
    $json = Json::decode($response);
    $this->assertIdentical($json[$id], '<ul class="contextual-links"><li class="block-configure"><a href="' . base_path() . 'admin/structure/block/manage/' . $block->id() . '">Configure block</a></li><li class="menu-ui-edit"><a href="' . base_path() . 'admin/structure/menu/manage/tools">Edit menu</a></li></ul>');
  }

  /**
   * Tests menu link bundles.
   */
  public function testMenuBundles() {
    $this->drupalLogin($this->admin_user);
    $menu = $this->addCustomMenu();
    // Clear the entity cache to ensure the static caches are rebuilt.
    \Drupal::entityManager()->clearCachedBundles();
    $bundles = entity_get_bundles('menu_link');
    $this->assertTrue(isset($bundles[$menu->id()]));
    $menus = menu_list_system_menus();
    $menus[$menu->id()] = $menu->label();
    ksort($menus);
    $this->assertIdentical(array_keys($bundles), array_keys($menus));

    // Test if moving a menu link between menus changes the bundle.
    $node = $this->drupalCreateNode(array('type' => 'article'));
    $item = $this->addMenuLink(0, 'node/' . $node->id(), 'tools');
    $this->moveMenuLink($item, 0, $menu->id());
    $this->assertEqual($item->bundle(), 'tools', 'Menu link bundle matches the menu');

    $moved_item = entity_load('menu_link', $item->id(), TRUE);
    $this->assertNotEqual($moved_item->bundle(), $item->bundle(), 'Menu link bundle was changed');
    $this->assertEqual($moved_item->bundle(), $menu->id(), 'Menu link bundle matches the menu');

    $unsaved_item = entity_create('menu_link', array('menu_name' => $menu->id(), 'link_title' => $this->randomName(16), 'link_path' => '<front>'));
    $this->assertEqual($unsaved_item->bundle(), $menu->id(), 'Unsaved menu link bundle matches the menu');
    $this->assertEqual($unsaved_item->menu_name, $menu->id(), 'Unsaved menu link menu name matches the menu');
  }

  /**
   * Adds a menu link using the UI.
   *
   * @param integer $plid
   *   Optional parent menu link id.
   * @param string $link
   *   Link path. Defaults to the front page.
   * @param string $menu_name
   *   Menu name. Defaults to 'tools'.
   * @param bool $expanded
   *   Whether or not this menu link is expanded. Setting this to TRUE should
   *   test whether it works when we do the authenticated_user tests. Defaults
   *   to FALSE.
   * @param string $weight
   *  Menu weight. Defaults to 0.
   * @param string $actual_link
   *   Actual link path in case $link is an alias.
   *
   * @return \Drupal\menu_link\Entity\MenuLink
   *   A menu link entity.
   */
  function addMenuLink($plid = 0, $link = '<front>', $menu_name = 'tools', $expanded = TRUE, $weight = '0', $actual_link = FALSE) {
    // View add menu link page.
    $this->drupalGet("admin/structure/menu/manage/$menu_name/add");
    $this->assertResponse(200);

    $title = '!link_' . $this->randomName(16);
    $edit = array(
      'link_path' => $link,
      'link_title' => $title,
      'description' => '',
      'enabled' => TRUE,
      'expanded' => $expanded,
      'parent' =>  $menu_name . ':' . $plid,
      'weight' => $weight,
    );

    if (!$actual_link) {
      $actual_link = $link;
    }
    // Add menu link.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText('The menu link has been saved.');

    $menu_links = entity_load_multiple_by_properties('menu_link', array('link_title' => $title));
    $menu_link = reset($menu_links);
    $this->assertTrue($menu_link, 'Menu link was found in database.');
    $this->assertMenuLink($menu_link->id(), array('menu_name' => $menu_name, 'link_path' => $actual_link, 'has_children' => 0, 'plid' => $plid));

    return $menu_link;
  }

  /**
   * Attempts to add menu link with invalid path or no access permission.
   */
  function addInvalidMenuLink() {
    foreach (array('-&-', 'admin/people/permissions', '#') as $link_path) {
      $edit = array(
        'link_path' => $link_path,
        'link_title' => 'title',
      );
      $this->drupalPostForm("admin/structure/menu/manage/{$this->menu->id()}/add", $edit, t('Save'));
      $this->assertRaw(t("The path '@path' is either invalid or you do not have access to it.", array('@path' => $link_path)), 'Menu link was not created');
    }
  }

  /**
   * Verifies a menu link using the UI.
   *
   * @param array $item
   *   Menu link.
   * @param object $item_node
   *   Menu link content node.
   * @param array $parent
   *   Parent menu link.
   * @param object $parent_node
   *   Parent menu link content node.
   */
  function verifyMenuLink($item, $item_node, $parent = NULL, $parent_node = NULL) {
    // View home page.
    $this->drupalGet('');
    $this->assertResponse(200);

    // Verify parent menu link.
    if (isset($parent)) {
      // Verify menu link.
      $title = $parent['link_title'];
      $this->assertLink($title, 0, 'Parent menu link was displayed');

      // Verify menu link link.
      $this->clickLink($title);
      $title = $parent_node->label();
      $this->assertTitle(t("@title | Drupal", array('@title' => $title)), 'Parent menu link link target was correct');
    }

    // Verify menu link.
    $title = $item['link_title'];
    $this->assertLink($title, 0, 'Menu link was displayed');

    // Verify menu link link.
    $this->clickLink($title);
    $title = $item_node->label();
    $this->assertTitle(t("@title | Drupal", array('@title' => $title)), 'Menu link link target was correct');
  }

  /**
   * Changes the parent of a menu link using the UI.
   *
   * @param array $item
   *   The menu link item to move.
   * @param int $plid
   *   The id of the new parent.
   * @param string $menu_name
   *   The menu the menu link will be moved to.
   */
  function moveMenuLink($item, $plid, $menu_name) {
    $mlid = $item['mlid'];

    $edit = array(
      'parent' => $menu_name . ':' . $plid,
    );
    $this->drupalPostForm("admin/structure/menu/item/$mlid/edit", $edit, t('Save'));
    $this->assertResponse(200);
  }

  /**
   * Modifies a menu link using the UI.
   *
   * @param array $item
   *   Menu link passed by reference.
   */
  function modifyMenuLink(&$item) {
    $item['link_title'] = $this->randomName(16);

    $mlid = $item['mlid'];
    $title = $item['link_title'];

    // Edit menu link.
    $edit = array();
    $edit['link_title'] = $title;
    $this->drupalPostForm("admin/structure/menu/item/$mlid/edit", $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText('The menu link has been saved.');
    // Verify menu link.
    $this->drupalGet('admin/structure/menu/manage/' . $item['menu_name']);
    $this->assertText($title, 'Menu link was edited');
  }

  /**
   * Resets a standard menu link using the UI.
   *
   * @param array $item
   *   Menu link.
   * @param string $old_title
   *   Original title for menu link.
   */
  function resetMenuLink($item, $old_title) {
    $mlid = $item['mlid'];
    $title = $item['link_title'];

    // Reset menu link.
    $this->drupalPostForm("admin/structure/menu/item/$mlid/reset", array(), t('Reset'));
    $this->assertResponse(200);
    $this->assertRaw(t('The menu link was reset to its default settings.'), 'Menu link was reset');

    // Verify menu link.
    $this->drupalGet('');
    $this->assertNoText($title, 'Menu link was reset');
    $this->assertText($old_title, 'Menu link was reset');
  }

  /**
   * Deletes a menu link using the UI.
   *
   * @param array $item
   *   Menu link.
   */
  function deleteMenuLink($item) {
    $mlid = $item['mlid'];
    $title = $item['link_title'];

    // Delete menu link.
    $this->drupalPostForm("admin/structure/menu/item/$mlid/delete", array(), t('Confirm'));
    $this->assertResponse(200);
    $this->assertRaw(t('The menu link %title has been deleted.', array('%title' => $title)), 'Menu link was deleted');

    // Verify deletion.
    $this->drupalGet('');
    $this->assertNoText($title, 'Menu link was deleted');
  }

  /**
   * Alternately disables and enables a menu link.
   *
   * @param $item
   *   Menu link.
   */
  function toggleMenuLink($item) {
    $this->disableMenuLink($item);

    // Verify menu link is absent.
    $this->drupalGet('');
    $this->assertNoText($item['link_title'], 'Menu link was not displayed');
    $this->enableMenuLink($item);

    // Verify menu link is displayed.
    $this->drupalGet('');
    $this->assertText($item['link_title'], 'Menu link was displayed');
  }

  /**
   * Disables a menu link.
   *
   * @param $item
   *   Menu link.
   */
  function disableMenuLink($item) {
    $mlid = $item['mlid'];
    $edit['enabled'] = FALSE;
    $this->drupalPostForm("admin/structure/menu/item/$mlid/edit", $edit, t('Save'));

    // Unlike most other modules, there is no confirmation message displayed.
    // Verify in the database.
    $this->assertMenuLink($mlid, array('hidden' => 1));
  }

  /**
   * Enables a menu link.
   *
   * @param $item
   *   Menu link.
   */
  function enableMenuLink($item) {
    $mlid = $item['mlid'];
    $edit['enabled'] = TRUE;
    $this->drupalPostForm("admin/structure/menu/item/$mlid/edit", $edit, t('Save'));

    // Verify in the database.
    $this->assertMenuLink($mlid, array('hidden' => 0));
  }

  /**
   * Tests if administrative users other than user 1 can access the menu parents
   * AJAX callback.
   */
  public function testMenuParentsJsAccess() {
    $admin = $this->drupalCreateUser(array('administer menu'));
    $this->drupalLogin($admin);
    // Just check access to the callback overall, the POST data is irrelevant.
    $this->drupalGetAJAX('admin/structure/menu/parents');
    $this->assertResponse(200);

    // Do standard user tests.
    // Login the user.
    $this->drupalLogin($this->authenticated_user);
    $this->drupalGetAJAX('admin/structure/menu/parents');
    $this->assertResponse(403);
  }

  /**
   * Returns standard menu link.
   *
   * @return \Drupal\menu_link\Entity\MenuLink
   *   A menu link entity.
   */
  private function getStandardMenuLink() {
    $mlid = 0;
    // Retrieve menu link id of the Log out menu link, which will always be on
    // the front page.
    $query = \Drupal::entityQuery('menu_link')
      ->condition('module', 'user')
      ->condition('machine_name', 'user.logout');
    $result = $query->execute();
    if (!empty($result)) {
      $mlid = reset($result);
    }

    $this->assertTrue($mlid > 0, 'Standard menu link id was found');
    // Load menu link.
    // Use api function so that link is translated for rendering.
    $item = entity_load('menu_link', $mlid);
    $this->assertTrue((bool) $item, 'Standard menu link was loaded');
    return $item;
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

    // View menu edit page.
    $item = $this->getStandardMenuLink();
    $this->drupalGet('admin/structure/menu/item/' . $item['mlid'] . '/edit');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText(t('Edit menu item'), 'Menu edit page was displayed');
    }

    // View menu settings page.
    $this->drupalGet('admin/structure/menu/settings');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText(t('Menus'), 'Menu settings page was displayed');
    }

    // View add menu page.
    $this->drupalGet('admin/structure/menu/add');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText(t('Menus'), 'Add menu page was displayed');
    }
  }

}
