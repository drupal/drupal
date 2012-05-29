<?php

/**
 * @file
 * Definition of Drupal\shortcut\Tests\ShortcutSetsTest.
 */

namespace Drupal\shortcut\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Defines shortcut set test cases.
 */
class ShortcutSetsTest extends ShortcutTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Shortcut set functionality',
      'description' => 'Create, view, edit, delete, and change shortcut sets.',
      'group' => 'Shortcut',
    );
  }

  /**
   * Tests creating a shortcut set.
   */
  function testShortcutSetAdd() {
    $new_set = $this->generateShortcutSet($this->randomName(10));
    $sets = shortcut_sets();
    $this->assertTrue(isset($sets[$new_set->set_name]), 'Successfully created a shortcut set.');
    $this->drupalGet('user/' . $this->admin_user->uid . '/shortcuts');
    $this->assertText($new_set->title, 'Generated shortcut set was listed as a choice on the user account page.');
  }

  /**
   * Tests switching a user's own shortcut set.
   */
  function testShortcutSetSwitchOwn() {
    $new_set = $this->generateShortcutSet($this->randomName(10));

    // Attempt to switch the default shortcut set to the newly created shortcut
    // set.
    $this->drupalPost('user/' . $this->admin_user->uid . '/shortcuts', array('set' => $new_set->set_name), t('Change set'));
    $this->assertResponse(200);
    $current_set = shortcut_current_displayed_set($this->admin_user);
    $this->assertTrue($new_set->set_name == $current_set->set_name, 'Successfully switched own shortcut set.');
  }

  /**
   * Tests switching another user's shortcut set.
   */
  function testShortcutSetAssign() {
    $new_set = $this->generateShortcutSet($this->randomName(10));

    shortcut_set_assign_user($new_set, $this->shortcut_user);
    $current_set = shortcut_current_displayed_set($this->shortcut_user);
    $this->assertTrue($new_set->set_name == $current_set->set_name, "Successfully switched another user's shortcut set.");
  }

  /**
   * Tests switching a user's shortcut set and creating one at the same time.
   */
  function testShortcutSetSwitchCreate() {
    $edit = array(
      'set' => 'new',
      'new' => $this->randomName(10),
    );
    $this->drupalPost('user/' . $this->admin_user->uid . '/shortcuts', $edit, t('Change set'));
    $current_set = shortcut_current_displayed_set($this->admin_user);
    $this->assertNotEqual($current_set->set_name, $this->set->set_name, 'A shortcut set can be switched to at the same time as it is created.');
    $this->assertEqual($current_set->title, $edit['new'], 'The new set is correctly assigned to the user.');
  }

  /**
   * Tests switching a user's shortcut set without providing a new set name.
   */
  function testShortcutSetSwitchNoSetName() {
    $edit = array('set' => 'new');
    $this->drupalPost('user/' . $this->admin_user->uid . '/shortcuts', $edit, t('Change set'));
    $this->assertText(t('The new set name is required.'));
    $current_set = shortcut_current_displayed_set($this->admin_user);
    $this->assertEqual($current_set->set_name, $this->set->set_name, 'Attempting to switch to a new shortcut set without providing a set name does not succeed.');
  }

  /**
   * Tests that shortcut_set_save() correctly updates existing links.
   */
  function testShortcutSetSave() {
    $set = $this->set;
    $old_mlids = $this->getShortcutInformation($set, 'mlid');

    $set->links[] = $this->generateShortcutLink('admin', $this->randomName(10));
    shortcut_set_save($set);
    $saved_set = shortcut_set_load($set->set_name);

    $new_mlids = $this->getShortcutInformation($saved_set, 'mlid');
    $this->assertTrue(count(array_intersect($old_mlids, $new_mlids)) == count($old_mlids), 'shortcut_set_save() did not inadvertently change existing mlids.');
  }

  /**
   * Tests renaming a shortcut set.
   */
  function testShortcutSetRename() {
    $set = $this->set;

    $new_title = $this->randomName(10);
    $this->drupalPost('admin/config/user-interface/shortcut/' . $set->set_name . '/edit', array('title' => $new_title), t('Save'));
    $set = shortcut_set_load($set->set_name);
    $this->assertTrue($set->title == $new_title, 'Shortcut set has been successfully renamed.');
  }

  /**
   * Tests renaming a shortcut set to the same name as another set.
   */
  function testShortcutSetRenameAlreadyExists() {
    $set = $this->generateShortcutSet($this->randomName(10));
    $existing_title = $this->set->title;
    $this->drupalPost('admin/config/user-interface/shortcut/' . $set->set_name . '/edit', array('title' => $existing_title), t('Save'));
    $this->assertRaw(t('The shortcut set %name already exists. Choose another name.', array('%name' => $existing_title)));
    $set = shortcut_set_load($set->set_name);
    $this->assertNotEqual($set->title, $existing_title, t('The shortcut set %title cannot be renamed to %new-title because a shortcut set with that title already exists.', array('%title' => $set->title, '%new-title' => $existing_title)));
  }

  /**
   * Tests unassigning a shortcut set.
   */
  function testShortcutSetUnassign() {
    $new_set = $this->generateShortcutSet($this->randomName(10));

    shortcut_set_assign_user($new_set, $this->shortcut_user);
    shortcut_set_unassign_user($this->shortcut_user);
    $current_set = shortcut_current_displayed_set($this->shortcut_user);
    $default_set = shortcut_default_set($this->shortcut_user);
    $this->assertTrue($current_set->set_name == $default_set->set_name, "Successfully unassigned another user's shortcut set.");
  }

  /**
   * Tests deleting a shortcut set.
   */
  function testShortcutSetDelete() {
    $new_set = $this->generateShortcutSet($this->randomName(10));

    $this->drupalPost('admin/config/user-interface/shortcut/' . $new_set->set_name . '/delete', array(), t('Delete'));
    $sets = shortcut_sets();
    $this->assertFalse(isset($sets[$new_set->set_name]), 'Successfully deleted a shortcut set.');
  }

  /**
   * Tests deleting the default shortcut set.
   */
  function testShortcutSetDeleteDefault() {
    $this->drupalGet('admin/config/user-interface/shortcut/' . SHORTCUT_DEFAULT_SET_NAME . '/delete');
    $this->assertResponse(403);
  }
}
