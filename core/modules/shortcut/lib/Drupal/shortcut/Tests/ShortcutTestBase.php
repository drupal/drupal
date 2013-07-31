<?php

/**
 * @file
 * Definition of Drupal\shortcut\Tests\ShortcutTestBase.
 */

namespace Drupal\shortcut\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Defines base class for shortcut test cases.
 */
abstract class ShortcutTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('toolbar', 'shortcut');

  /**
   * User with permission to administer shortcuts.
   */
  protected $admin_user;

  /**
   * User with permission to use shortcuts, but not administer them.
   */
  protected $shortcut_user;

  /**
   * Generic node used for testing.
   */
  protected $node;

  /**
   * Site-wide default shortcut set.
   */
  protected $set;

  function setUp() {
    parent::setUp();

    if ($this->profile != 'standard') {
      // Create Basic page and Article node types.
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

      // Populate the default shortcut set.
      $shortcut_set = shortcut_set_load('default');
      $menu_link = entity_create('menu_link', array(
        'link_path' => 'node/add',
        'link_title' => t('Add content'),
        'weight' => -20,
      ));
      $menu_link->save();
      $shortcut_set->links[$menu_link->uuid()] = $menu_link;
      $menu_item = entity_create('menu_link', array(
        'link_path' => 'admin/content',
        'link_title' => t('All content'),
        'weight' => -19,
      ));
      $menu_item->save();
      $shortcut_set->links[$menu_item->uuid()] = $menu_item;
      $shortcut_set->save();
    }

    // Create users.
    $this->admin_user = $this->drupalCreateUser(array('access toolbar', 'administer shortcuts', 'view the administration theme', 'create article content', 'create page content', 'access content overview'));
    $this->shortcut_user = $this->drupalCreateUser(array('customize shortcut links', 'switch shortcut sets'));

    // Create a node.
    $this->node = $this->drupalCreateNode(array('type' => 'article'));

    // Log in as admin and grab the default shortcut set.
    $this->drupalLogin($this->admin_user);
    $this->set = shortcut_set_load('default');
    shortcut_set_assign_user($this->set, $this->admin_user);
  }

  /**
   * Creates a generic shortcut set.
   */
  function generateShortcutSet($label = '', $id = NULL, $default_links = TRUE) {
    $set = entity_create('shortcut_set', array(
      'id' => isset($id) ? $id : strtolower($this->randomName()),
      'label' => empty($label) ? $this->randomString() : $label,
    ));
    if ($default_links) {
      $menu_link = $this->generateShortcutLink('node/add');
      $set->links[$menu_link->uuid()] = $menu_link;
      $menu_link = $this->generateShortcutLink('admin/content');
      $set->links[$menu_link->uuid()] = $menu_link;
    }
    $set->save();
    return $set;
  }

  /**
   * Creates a generic shortcut link.
   */
  function generateShortcutLink($path, $title = '') {
    $link = entity_create('menu_link', array(
      'link_path' => $path,
      'link_title' => !empty($title) ? $title : $this->randomName(),
    ));
    $link->save();

    return $link;
  }

  /**
   * Extracts information from shortcut set links.
   *
   * @param object $set
   *   The shortcut set object to extract information from.
   * @param string $key
   *   The array key indicating what information to extract from each link:
   *    - 'link_path': Extract link paths.
   *    - 'link_title': Extract link titles.
   *    - 'mlid': Extract the menu link item ID numbers.
   *
   * @return array
   *   Array of the requested information from each link.
   */
  function getShortcutInformation($set, $key) {
    $info = array();
    foreach ($set->links as $uuid => $link) {
      $info[] = $link->{$key};
    }
    return $info;
  }
}
