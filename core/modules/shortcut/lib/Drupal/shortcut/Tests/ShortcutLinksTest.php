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
  public static $modules = array('router_test');

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
  function testShortcutLinkAdd() {
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
        'shortcut_link[link_title]' => $title,
        'shortcut_link[link_path]'  => $test['path'],
      );
      $this->drupalPost('admin/config/user-interface/shortcut/manage/' . $set->id() . '/add-link', $form_data, t('Save'));
      $this->assertResponse(200);
      $saved_set = shortcut_set_load($set->id());
      $paths = $this->getShortcutInformation($saved_set, 'link_path');
      $test_path = empty($test['path']) ? '<front>' : $test['path'];
      $this->assertTrue(in_array($this->container->get('path.alias_manager')->getSystemPath($test_path), $paths), 'Shortcut created: '. $test['path']);
      $this->assertLink($title, 0, 'Shortcut link found on the page.');
    }
  }

  /**
   * Tests that the "add to shortcut" link changes to "remove shortcut".
   */
  function testShortcutQuickLink() {
    theme_enable(array('seven'));
    \Drupal::config('system.theme')->set('admin', 'seven')->save();
    $this->container->get('config.factory')->get('node.settings')->set('use_admin_theme', '1')->save();

    $link = reset($this->set->links);

    $this->drupalGet($link->link_path);
    $this->assertRaw(t('Remove from %title shortcuts', array('%title' => $this->set->label())), '"Add to shortcuts" link properly switched to "Remove from shortcuts".');
  }

  /**
   * Tests that shortcut links can be renamed.
   */
  function testShortcutLinkRename() {
    $set = $this->set;

    // Attempt to rename shortcut link.
    $new_link_name = $this->randomName();

    $link = reset($set->links);
    $this->drupalPost('admin/config/user-interface/shortcut/link/' . $link->mlid, array('shortcut_link[link_title]' => $new_link_name, 'shortcut_link[link_path]' => $link->link_path), t('Save'));
    $saved_set = shortcut_set_load($set->id());
    $titles = $this->getShortcutInformation($saved_set, 'link_title');
    $this->assertTrue(in_array($new_link_name, $titles), 'Shortcut renamed: ' . $new_link_name);
    $this->assertLink($new_link_name, 0, 'Renamed shortcut link appears on the page.');
  }

  /**
   * Tests that changing the path of a shortcut link works.
   */
  function testShortcutLinkChangePath() {
    $set = $this->set;

    // Tests changing a shortcut path.
    $new_link_path = 'admin/config';

    $link = reset($set->links);
    $this->drupalPost('admin/config/user-interface/shortcut/link/' . $link->mlid, array('shortcut_link[link_title]' => $link->link_title, 'shortcut_link[link_path]' => $new_link_path), t('Save'));
    $saved_set = shortcut_set_load($set->id());
    $paths = $this->getShortcutInformation($saved_set, 'link_path');
    $this->assertTrue(in_array($new_link_path, $paths), 'Shortcut path changed: ' . $new_link_path);
    $this->assertLinkByHref($new_link_path, 0, 'Shortcut with new path appears on the page.');
  }

  /**
   * Tests deleting a shortcut link.
   */
  function testShortcutLinkDelete() {
    $set = $this->set;

    $link = reset($set->links);
    $this->drupalPost('admin/config/user-interface/shortcut/link/' . $link->mlid . '/delete', array(), 'Delete');
    $saved_set = shortcut_set_load($set->id());
    $mlids = $this->getShortcutInformation($saved_set, 'mlid');
    $this->assertFalse(in_array($link->mlid, $mlids), 'Successfully deleted a shortcut.');

    // Delete all the remaining shortcut menu links.
    foreach (array_filter($mlids) as $mlid) {
      menu_link_delete($mlid);
    }

    // Get the front page to check that no exceptions occur.
    $this->drupalGet('');
  }

  /**
   * Tests that the add shortcut link is not displayed for 404/403 errors.
   *
   * Tests that the "Add to shortcuts" link is not displayed on a page not
   * found or a page the user does not have access to.
   */
  function testNoShortcutLink() {
    // Change to a theme that displays shortcuts.
    \Drupal::config('system.theme')
      ->set('default', 'seven')
      ->save();

    $this->drupalGet('page-that-does-not-exist');
    $this->assertNoRaw('add-shortcut', 'Add to shortcuts link was not shown on a page not found.');

    // The user does not have access to this path.
    $this->drupalGet('admin/modules');
    $this->assertNoRaw('add-shortcut', 'Add to shortcuts link was not shown on a page the user does not have access to.');

    // Verify that the testing mechanism works by verifying the shortcut
    // link appears on admin/content/node.
    $this->drupalGet('admin/content/node');
    $this->assertRaw('add-shortcut', 'Add to shortcuts link was shown on a page the user does have access to.');
  }
}
