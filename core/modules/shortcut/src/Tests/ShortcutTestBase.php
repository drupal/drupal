<?php

/**
 * @file
 * Definition of Drupal\shortcut\Tests\ShortcutTestBase.
 */

namespace Drupal\shortcut\Tests;

use Drupal\shortcut\Entity\Shortcut;
use Drupal\shortcut\Entity\ShortcutSet;
use Drupal\shortcut\ShortcutSetInterface;
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
  public static $modules = array('node', 'toolbar', 'shortcut');

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
   *
   * @var \Drupal\shortcut\ShortcutSetInterface
   */
  protected $set;

  function setUp() {
    parent::setUp();

    if ($this->profile != 'standard') {
      // Create Basic page and Article node types.
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

      // Populate the default shortcut set.
      $shortcut = Shortcut::create(array(
        'set' => 'default',
        'title' => t('Add content'),
        'weight' => -20,
        'path' => 'node/add',
      ));
      $shortcut->save();

      $shortcut = Shortcut::create(array(
        'set' => 'default',
        'title' => t('All content'),
        'weight' => -19,
        'path' => 'admin/content',
      ));
      $shortcut->save();
    }

    // Create users.
    $this->admin_user = $this->drupalCreateUser(array('access toolbar', 'administer shortcuts', 'view the administration theme', 'create article content', 'create page content', 'access content overview', 'administer users'));
    $this->shortcut_user = $this->drupalCreateUser(array('customize shortcut links', 'switch shortcut sets'));

    // Create a node.
    $this->node = $this->drupalCreateNode(array('type' => 'article'));

    // Log in as admin and grab the default shortcut set.
    $this->drupalLogin($this->admin_user);
    $this->set = ShortcutSet::load('default');
    shortcut_set_assign_user($this->set, $this->admin_user);
  }

  /**
   * Creates a generic shortcut set.
   */
  function generateShortcutSet($label = '', $id = NULL) {
    $set = ShortcutSet::create(array(
      'id' => isset($id) ? $id : strtolower($this->randomName()),
      'label' => empty($label) ? $this->randomString() : $label,
    ));
    $set->save();
    return $set;
  }

  /**
   * Extracts information from shortcut set links.
   *
   * @param \Drupal\shortcut\ShortcutSetInterface $set
   *   The shortcut set object to extract information from.
   * @param string $key
   *   The array key indicating what information to extract from each link:
   *    - 'title': Extract shortcut titles.
   *    - 'path': Extract shortcut paths.
   *    - 'id': Extract the shortcut ID.
   *
   * @return array
   *   Array of the requested information from each link.
   */
  function getShortcutInformation(ShortcutSetInterface $set, $key) {
    $info = array();
    \Drupal::entityManager()->getStorage('shortcut')->resetCache();
    foreach ($set->getShortcuts() as $shortcut) {
      $info[] = $shortcut->{$key}->value;
    }
    return $info;
  }

}
