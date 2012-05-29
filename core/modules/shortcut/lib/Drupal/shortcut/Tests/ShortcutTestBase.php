<?php

/**
 * @file
 * Definition of Drupal\shortcut\Tests\ShortcutTestBase.
 */

namespace Drupal\shortcut\Tests;

use Drupal\simpletest\WebTestBase;
use stdClass;

/**
 * Defines base class for shortcut test cases.
 */
class ShortcutTestBase extends WebTestBase {

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
    parent::setUp('toolbar', 'shortcut');

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }

    // Create users.
    $this->admin_user = $this->drupalCreateUser(array('access toolbar', 'administer shortcuts', 'view the administration theme', 'create article content', 'create page content', 'access content overview'));
    $this->shortcut_user = $this->drupalCreateUser(array('customize shortcut links', 'switch shortcut sets'));

    // Create a node.
    $this->node = $this->drupalCreateNode(array('type' => 'article'));

    // Log in as admin and grab the default shortcut set.
    $this->drupalLogin($this->admin_user);
    $this->set = shortcut_set_load(SHORTCUT_DEFAULT_SET_NAME);
    shortcut_set_assign_user($this->set, $this->admin_user);
  }

  /**
   * Creates a generic shortcut set.
   */
  function generateShortcutSet($title = '', $default_links = TRUE) {
    $set = new stdClass();
    $set->title = empty($title) ? $this->randomName(10) : $title;
    if ($default_links) {
      $set->links = array();
      $set->links[] = $this->generateShortcutLink('node/add');
      $set->links[] = $this->generateShortcutLink('admin/content');
    }
    shortcut_set_save($set);

    return $set;
  }

  /**
   * Creates a generic shortcut link.
   */
  function generateShortcutLink($path, $title = '') {
    $link = array(
      'link_path' => $path,
      'link_title' => !empty($title) ? $title : $this->randomName(10),
    );

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
    foreach ($set->links as $link) {
      $info[] = $link[$key];
    }
    return $info;
  }
}
