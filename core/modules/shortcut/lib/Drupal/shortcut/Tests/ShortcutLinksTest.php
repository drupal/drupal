<?php

/**
 * @file
 * Definition of Drupal\shortcut\Tests\ShortcutLinksTest.
 */

namespace Drupal\shortcut\Tests;

/**
 * Defines shortcut links test cases.
 */
class ShortcutLinksTest extends ShortcutTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('router_test', 'views');

  public static function getInfo() {
    return array(
      'name' => 'Shortcut link functionality',
      'description' => 'Create, view, edit, delete, and change shortcut links.',
      'group' => 'Shortcut',
    );
  }

  /**
   * Tests that creating a shortcut works properly.
   */
  public function testShortcutLinkAdd() {
    $set = $this->set;

    // Create an alias for the node so we can test aliases.
    $path = array(
      'source' => 'node/' . $this->node->id(),
      'alias' => $this->randomName(8),
    );
    $this->container->get('path.crud')->save($path['source'], $path['alias']);

    // Create some paths to test.
    $test_cases = array(
      array('path' => ''),
      array('path' => 'admin'),
      array('path' => 'admin/config/system/site-information'),
      array('path' => 'node/' . $this->node->id() . '/edit'),
      array('path' => $path['alias']),
      array('path' => 'router_test/test2'),
      array('path' => 'router_test/test3/value'),
    );

    // Check that each new shortcut links where it should.
    foreach ($test_cases as $test) {
      $title = $this->randomName();
      $form_data = array(
        'title' => $title,
        'path' => $test['path'],
      );
      $this->drupalPostForm('admin/config/user-interface/shortcut/manage/' . $set->id() . '/add-link', $form_data, t('Save'));
      $this->assertResponse(200);
      $saved_set = shortcut_set_load($set->id());
      $paths = $this->getShortcutInformation($saved_set, 'path');
      $this->assertTrue(in_array($this->container->get('path.alias_manager')->getSystemPath($test['path']), $paths), 'Shortcut created: ' . $test['path']);
      $this->assertLink($title, 0, 'Shortcut link found on the page.');
    }
  }

  /**
   * Tests that the "add to shortcut" and "remove from shortcut" links work.
   */
  public function testShortcutQuickLink() {
    theme_enable(array('seven'));
    \Drupal::config('system.theme')->set('admin', 'seven')->save();
    $this->container->get('config.factory')->get('node.settings')->set('use_admin_theme', '1')->save();

    $this->drupalLogin($this->root_user);
    $this->drupalGet('admin/config/system/cron');

    // Test the "Add to shortcuts" link.
    $this->clickLink('Add to Default shortcuts');
    $this->assertText('Added a shortcut for Cron.');
    $this->assertLink('Cron', 0, 'Shortcut link found on page');

    $this->drupalGet('admin/structure');
    $this->assertLink('Cron', 0, 'Shortcut link found on different page');

    // Test the "Remove from shortcuts" link.
    $this->clickLink('Cron');
    $this->clickLink('Remove from Default shortcuts');
    $this->drupalPostForm(NULL, array(), 'Delete');
    $this->assertText('The shortcut Cron has been deleted.');
    $this->assertNoLink('Cron', 'Shortcut link removed from page');

    $this->drupalGet('admin/structure');
    $this->assertNoLink('Cron', 'Shortcut link removed from different page');
  }

  /**
   * Tests that shortcut links can be renamed.
   */
  public function testShortcutLinkRename() {
    $set = $this->set;

    // Attempt to rename shortcut link.
    $new_link_name = $this->randomName();

    $shortcuts = $set->getShortcuts();
    $shortcut = reset($shortcuts);
    $this->drupalPostForm('admin/config/user-interface/shortcut/link/' . $shortcut->id(), array('title' => $new_link_name, 'path' => $shortcut->path->value), t('Save'));
    $saved_set = shortcut_set_load($set->id());
    $titles = $this->getShortcutInformation($saved_set, 'title');
    $this->assertTrue(in_array($new_link_name, $titles), 'Shortcut renamed: ' . $new_link_name);
    $this->assertLink($new_link_name, 0, 'Renamed shortcut link appears on the page.');
  }

  /**
   * Tests that changing the path of a shortcut link works.
   */
  public function testShortcutLinkChangePath() {
    $set = $this->set;

    // Tests changing a shortcut path.
    $new_link_path = 'admin/config';

    $shortcuts = $set->getShortcuts();
    $shortcut = reset($shortcuts);
    $this->drupalPostForm('admin/config/user-interface/shortcut/link/' . $shortcut->id(), array('title' => $shortcut->getTitle(), 'path' => $new_link_path), t('Save'));
    $saved_set = shortcut_set_load($set->id());
    $paths = $this->getShortcutInformation($saved_set, 'path');
    $this->assertTrue(in_array($new_link_path, $paths), 'Shortcut path changed: ' . $new_link_path);
    $this->assertLinkByHref($new_link_path, 0, 'Shortcut with new path appears on the page.');
  }

  /**
   * Tests that changing the route of a shortcut link works.
   */
  public function testShortcutLinkChangeRoute() {
    $this->drupalLogin($this->root_user);
    $this->drupalGet('admin/content');
    $this->assertResponse(200);
    // Disable the view.
    entity_load('view', 'content')->disable()->save();
    $this->drupalGet('admin/content');
    $this->assertResponse(200);
  }

  /**
   * Tests deleting a shortcut link.
   */
  public function testShortcutLinkDelete() {
    $set = $this->set;

    $shortcuts = $set->getShortcuts();
    $shortcut = reset($shortcuts);
    $this->drupalPostForm('admin/config/user-interface/shortcut/link/' . $shortcut->id() . '/delete', array(), 'Delete');
    $saved_set = shortcut_set_load($set->id());
    $ids = $this->getShortcutInformation($saved_set, 'id');
    $this->assertFalse(in_array($shortcut->id(), $ids), 'Successfully deleted a shortcut.');

    // Delete all the remaining shortcut links.
    entity_delete_multiple('shortcut', array_filter($ids));

    // Get the front page to check that no exceptions occur.
    $this->drupalGet('');
  }

  /**
   * Tests that the add shortcut link is not displayed for 404/403 errors.
   *
   * Tests that the "Add to shortcuts" link is not displayed on a page not
   * found or a page the user does not have access to.
   */
  public function testNoShortcutLink() {
    // Change to a theme that displays shortcuts.
    theme_enable(array('seven'));
    \Drupal::config('system.theme')
      ->set('default', 'seven')
      ->save();

    $this->drupalGet('page-that-does-not-exist');
    $result = $this->xpath('//div[contains(@class, "add-shortcut")]');
    $this->assertTrue(empty($result), 'Add to shortcuts link was not shown on a page not found.');

    // The user does not have access to this path.
    $this->drupalGet('admin/modules');
    $result = $this->xpath('//div[contains(@class, "add-shortcut")]');
    $this->assertTrue(empty($result), 'Add to shortcuts link was not shown on a page the user does not have access to.');

    // Verify that the testing mechanism works by verifying the shortcut
    // link appears on admin/people.
    $this->drupalGet('admin/people');
    $result = $this->xpath('//div[contains(@class, "remove-shortcut")]');
    $this->assertTrue(!empty($result), 'Remove from shortcuts link was shown on a page the user does have access to.');

    // Verify that the shortcut link appears on routing only pages.
    $this->drupalGet('router_test/test2');
    $result = $this->xpath('//div[contains(@class, "add-shortcut")]');
    $this->assertTrue(!empty($result), 'Add to shortcuts link was shown on a page the user does have access to.');
  }

}
