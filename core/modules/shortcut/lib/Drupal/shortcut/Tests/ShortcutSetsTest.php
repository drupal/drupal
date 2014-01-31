<?php

/**
 * @file
 * Definition of Drupal\shortcut\Tests\ShortcutSetsTest.
 */

namespace Drupal\shortcut\Tests;

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
    $this->drupalGet('admin/config/user-interface/shortcut');
    $this->clickLink(t('Add shortcut set'));
    $edit = array(
      'label' => $this->randomName(),
      'id' => strtolower($this->randomName()),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $new_set = $this->container->get('entity.manager')->getStorageController('shortcut_set')->load($edit['id']);
    $this->assertIdentical($new_set->id(), $edit['id'], 'Successfully created a shortcut set.');
    $this->drupalGet('user/' . $this->admin_user->id() . '/shortcuts');
    $this->assertText($new_set->label(), 'Generated shortcut set was listed as a choice on the user account page.');
  }

  /**
   * Tests switching a user's own shortcut set.
   */
  function testShortcutSetSwitchOwn() {
    $new_set = $this->generateShortcutSet($this->randomName());

    // Attempt to switch the default shortcut set to the newly created shortcut
    // set.
    $this->drupalPostForm('user/' . $this->admin_user->id() . '/shortcuts', array('set' => $new_set->id()), t('Change set'));
    $this->assertResponse(200);
    $current_set = shortcut_current_displayed_set($this->admin_user);
    $this->assertTrue($new_set->id() == $current_set->id(), 'Successfully switched own shortcut set.');
  }

  /**
   * Tests switching another user's shortcut set.
   */
  function testShortcutSetAssign() {
    $new_set = $this->generateShortcutSet($this->randomName());

    shortcut_set_assign_user($new_set, $this->shortcut_user);
    $current_set = shortcut_current_displayed_set($this->shortcut_user);
    $this->assertTrue($new_set->id() == $current_set->id(), "Successfully switched another user's shortcut set.");
  }

  /**
   * Tests switching a user's shortcut set and creating one at the same time.
   */
  function testShortcutSetSwitchCreate() {
    $edit = array(
      'set' => 'new',
      'id' => strtolower($this->randomName()),
      'label' => $this->randomString(),
    );
    $this->drupalPostForm('user/' . $this->admin_user->id() . '/shortcuts', $edit, t('Change set'));
    $current_set = shortcut_current_displayed_set($this->admin_user);
    $this->assertNotEqual($current_set->id(), $this->set->id(), 'A shortcut set can be switched to at the same time as it is created.');
    $this->assertEqual($current_set->label(), $edit['label'], 'The new set is correctly assigned to the user.');
  }

  /**
   * Tests switching a user's shortcut set without providing a new set name.
   */
  function testShortcutSetSwitchNoSetName() {
    $edit = array('set' => 'new');
    $this->drupalPostForm('user/' . $this->admin_user->id() . '/shortcuts', $edit, t('Change set'));
    $this->assertText(t('The new set label is required.'));
    $current_set = shortcut_current_displayed_set($this->admin_user);
    $this->assertEqual($current_set->id(), $this->set->id(), 'Attempting to switch to a new shortcut set without providing a set name does not succeed.');
  }

  /**
   * Tests renaming a shortcut set.
   */
  function testShortcutSetRename() {
    $set = $this->set;

    $new_label = $this->randomName();
    $this->drupalGet('admin/config/user-interface/shortcut');
    $this->clickLink(t('Edit shortcut set'));
    $this->drupalPostForm(NULL, array('label' => $new_label), t('Save'));
    $set = shortcut_set_load($set->id());
    $this->assertTrue($set->label() == $new_label, 'Shortcut set has been successfully renamed.');
  }

  /**
   * Tests renaming a shortcut set to the same name as another set.
   */
  function testShortcutSetRenameAlreadyExists() {
    $set = $this->generateShortcutSet($this->randomName());
    $existing_label = $this->set->label();
    $this->drupalPostForm('admin/config/user-interface/shortcut/manage/' . $set->id(), array('label' => $existing_label), t('Save'));
    $this->assertRaw(t('The shortcut set %name already exists. Choose another name.', array('%name' => $existing_label)));
    $set = shortcut_set_load($set->id());
    $this->assertNotEqual($set->label(), $existing_label, format_string('The shortcut set %title cannot be renamed to %new-title because a shortcut set with that title already exists.', array('%title' => $set->label(), '%new-title' => $existing_label)));
  }

  /**
   * Tests unassigning a shortcut set.
   */
  function testShortcutSetUnassign() {
    $new_set = $this->generateShortcutSet($this->randomName());

    shortcut_set_assign_user($new_set, $this->shortcut_user);
    shortcut_set_unassign_user($this->shortcut_user);
    $current_set = shortcut_current_displayed_set($this->shortcut_user);
    $default_set = shortcut_default_set($this->shortcut_user);
    $this->assertTrue($current_set->id() == $default_set->id(), "Successfully unassigned another user's shortcut set.");
  }

  /**
   * Tests deleting a shortcut set.
   */
  function testShortcutSetDelete() {
    $new_set = $this->generateShortcutSet($this->randomName());

    $this->drupalPostForm('admin/config/user-interface/shortcut/manage/' . $new_set->id() . '/delete', array(), t('Delete'));
    $sets = entity_load_multiple('shortcut_set');
    $this->assertFalse(isset($sets[$new_set->id()]), 'Successfully deleted a shortcut set.');
  }

  /**
   * Tests deleting the default shortcut set.
   */
  function testShortcutSetDeleteDefault() {
    $this->drupalGet('admin/config/user-interface/shortcut/manage/default/delete');
    $this->assertResponse(403);
  }

  /**
   * Tests creating a new shortcut set with a defined set name.
   */
  function testShortcutSetCreateWithSetName() {
    $random_name = $this->randomName();
    $new_set = $this->generateShortcutSet($random_name, $random_name);
    $sets = entity_load_multiple('shortcut_set');
    $this->assertTrue(isset($sets[$random_name]), 'Successfully created a shortcut set with a defined set name.');
    $this->drupalGet('user/' . $this->admin_user->id() . '/shortcuts');
    $this->assertText($new_set->label(), 'Generated shortcut set was listed as a choice on the user account page.');
  }
}
