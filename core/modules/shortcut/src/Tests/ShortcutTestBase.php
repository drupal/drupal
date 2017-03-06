<?php

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
  public static $modules = ['node', 'toolbar', 'shortcut'];

  /**
   * User with permission to administer shortcuts.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * User with permission to use shortcuts, but not administer them.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $shortcutUser;

  /**
   * Generic node used for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Site-wide default shortcut set.
   *
   * @var \Drupal\shortcut\ShortcutSetInterface
   */
  protected $set;

  protected function setUp() {
    parent::setUp();

    if ($this->profile != 'standard') {
      // Create Basic page and Article node types.
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

      // Populate the default shortcut set.
      $shortcut = Shortcut::create([
        'shortcut_set' => 'default',
        'title' => t('Add content'),
        'weight' => -20,
        'link' => [
          'uri' => 'internal:/node/add',
        ],
      ]);
      $shortcut->save();

      $shortcut = Shortcut::create([
        'shortcut_set' => 'default',
        'title' => t('All content'),
        'weight' => -19,
        'link' => [
          'uri' => 'internal:/admin/content',
        ],
      ]);
      $shortcut->save();
    }

    // Create users.
    $this->adminUser = $this->drupalCreateUser(['access toolbar', 'administer shortcuts', 'view the administration theme', 'create article content', 'create page content', 'access content overview', 'administer users', 'link to any page', 'edit any article content']);
    $this->shortcutUser = $this->drupalCreateUser(['customize shortcut links', 'switch shortcut sets', 'access shortcuts', 'access content']);

    // Create a node.
    $this->node = $this->drupalCreateNode(['type' => 'article']);

    // Log in as admin and grab the default shortcut set.
    $this->drupalLogin($this->adminUser);
    $this->set = ShortcutSet::load('default');
    \Drupal::entityManager()->getStorage('shortcut_set')->assignUser($this->set, $this->adminUser);
  }

  /**
   * Creates a generic shortcut set.
   */
  public function generateShortcutSet($label = '', $id = NULL) {
    $set = ShortcutSet::create([
      'id' => isset($id) ? $id : strtolower($this->randomMachineName()),
      'label' => empty($label) ? $this->randomString() : $label,
    ]);
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
   *    - 'link': Extract shortcut paths.
   *    - 'id': Extract the shortcut ID.
   *
   * @return array
   *   Array of the requested information from each link.
   */
  public function getShortcutInformation(ShortcutSetInterface $set, $key) {
    $info = [];
    \Drupal::entityManager()->getStorage('shortcut')->resetCache();
    foreach ($set->getShortcuts() as $shortcut) {
      if ($key == 'link') {
        $info[] = $shortcut->link->uri;
      }
      else {
        $info[] = $shortcut->{$key}->value;
      }
    }
    return $info;
  }

}
