<?php

namespace Drupal\Tests\shortcut\Functional;

use Drupal\shortcut\Entity\ShortcutSet;

/**
 * Create, view, edit, delete, and change shortcut sets.
 *
 * @group shortcut
 */
class ShortcutSetsTest extends ShortcutTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests creating a shortcut set.
   */
  public function testShortcutSetAdd() {
    $this->drupalGet('admin/config/user-interface/shortcut');
    $this->clickLink(t('Add shortcut set'));
    $edit = [
      'label' => $this->randomMachineName(),
      'id' => strtolower($this->randomMachineName()),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $new_set = $this->container->get('entity_type.manager')->getStorage('shortcut_set')->load($edit['id']);
    $this->assertIdentical($new_set->id(), $edit['id'], 'Successfully created a shortcut set.');
    $this->drupalGet('user/' . $this->adminUser->id() . '/shortcuts');
    $this->assertText($new_set->label(), 'Generated shortcut set was listed as a choice on the user account page.');
  }

  /**
   * Tests editing a shortcut set.
   */
  public function testShortcutSetEdit() {
    $set = $this->set;
    $shortcuts = $set->getShortcuts();

    // Visit the shortcut set edit admin ui.
    $this->drupalGet('admin/config/user-interface/shortcut/manage/' . $set->id() . '/customize');

    // Test for the page title.
    $this->assertTitle(t('List links') . ' | Drupal');

    // Test for the table.
    $element = $this->xpath('//div[@class="layout-content"]//table');
    $this->assertNotEmpty($element, 'Shortcut entity list table found.');

    // Test the table header.
    $elements = $this->xpath('//div[@class="layout-content"]//table/thead/tr/th');
    $this->assertEqual(count($elements), 3, 'Correct number of table header cells found.');

    // Test the contents of each th cell.
    $expected_items = [t('Name'), t('Weight'), t('Operations')];
    foreach ($elements as $key => $element) {
      $this->assertEqual($element->getText(), $expected_items[$key]);
    }

    // Look for test shortcuts in the table.
    $weight = count($shortcuts);
    $edit = [];
    foreach ($shortcuts as $shortcut) {
      $title = $shortcut->getTitle();

      // Confirm that a link to the shortcut is found within the table.
      $this->assertLink($title);

      // Look for a test shortcut weight select form element.
      $this->assertFieldByName('shortcuts[links][' . $shortcut->id() . '][weight]');

      // Change the weight of the shortcut.
      $edit['shortcuts[links][' . $shortcut->id() . '][weight]'] = $weight;
      $weight--;
    }

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('The shortcut set has been updated.'));

    \Drupal::entityTypeManager()->getStorage('shortcut')->resetCache();
    // Check to ensure that the shortcut weights have changed and that
    // ShortcutSet::.getShortcuts() returns shortcuts in the new order.
    $this->assertIdentical(array_reverse(array_keys($shortcuts)), array_keys($set->getShortcuts()));
  }

  /**
   * Tests switching a user's own shortcut set.
   */
  public function testShortcutSetSwitchOwn() {
    $new_set = $this->generateShortcutSet($this->randomMachineName());

    // Attempt to switch the default shortcut set to the newly created shortcut
    // set.
    $this->drupalPostForm('user/' . $this->adminUser->id() . '/shortcuts', ['set' => $new_set->id()], t('Change set'));
    $this->assertResponse(200);
    $current_set = shortcut_current_displayed_set($this->adminUser);
    $this->assertTrue($new_set->id() == $current_set->id(), 'Successfully switched own shortcut set.');
  }

  /**
   * Tests switching another user's shortcut set.
   */
  public function testShortcutSetAssign() {
    $new_set = $this->generateShortcutSet($this->randomMachineName());

    \Drupal::entityTypeManager()->getStorage('shortcut_set')->assignUser($new_set, $this->shortcutUser);
    $current_set = shortcut_current_displayed_set($this->shortcutUser);
    $this->assertTrue($new_set->id() == $current_set->id(), "Successfully switched another user's shortcut set.");
  }

  /**
   * Tests switching a user's shortcut set and creating one at the same time.
   */
  public function testShortcutSetSwitchCreate() {
    $edit = [
      'set' => 'new',
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
    ];
    $this->drupalPostForm('user/' . $this->adminUser->id() . '/shortcuts', $edit, t('Change set'));
    $current_set = shortcut_current_displayed_set($this->adminUser);
    $this->assertNotEqual($current_set->id(), $this->set->id(), 'A shortcut set can be switched to at the same time as it is created.');
    $this->assertEqual($current_set->label(), $edit['label'], 'The new set is correctly assigned to the user.');
  }

  /**
   * Tests switching a user's shortcut set without providing a new set name.
   */
  public function testShortcutSetSwitchNoSetName() {
    $edit = ['set' => 'new'];
    $this->drupalPostForm('user/' . $this->adminUser->id() . '/shortcuts', $edit, t('Change set'));
    $this->assertText(t('The new set label is required.'));
    $current_set = shortcut_current_displayed_set($this->adminUser);
    $this->assertEqual($current_set->id(), $this->set->id(), 'Attempting to switch to a new shortcut set without providing a set name does not succeed.');
    $this->assertFieldByXPath("//input[@name='label' and contains(concat(' ', normalize-space(@class), ' '), ' error ')]", NULL, 'The new set label field has the error class');
  }

  /**
   * Tests renaming a shortcut set.
   */
  public function testShortcutSetRename() {
    $set = $this->set;

    $new_label = $this->randomMachineName();
    $this->drupalGet('admin/config/user-interface/shortcut');
    $this->clickLink(t('Edit shortcut set'));
    $this->drupalPostForm(NULL, ['label' => $new_label], t('Save'));
    $set = ShortcutSet::load($set->id());
    $this->assertTrue($set->label() == $new_label, 'Shortcut set has been successfully renamed.');
  }

  /**
   * Tests unassigning a shortcut set.
   */
  public function testShortcutSetUnassign() {
    $new_set = $this->generateShortcutSet($this->randomMachineName());

    $shortcut_set_storage = \Drupal::entityTypeManager()->getStorage('shortcut_set');
    $shortcut_set_storage->assignUser($new_set, $this->shortcutUser);
    $shortcut_set_storage->unassignUser($this->shortcutUser);
    $current_set = shortcut_current_displayed_set($this->shortcutUser);
    $default_set = shortcut_default_set($this->shortcutUser);
    $this->assertTrue($current_set->id() == $default_set->id(), "Successfully unassigned another user's shortcut set.");
  }

  /**
   * Tests deleting a shortcut set.
   */
  public function testShortcutSetDelete() {
    $new_set = $this->generateShortcutSet($this->randomMachineName());

    $this->drupalPostForm('admin/config/user-interface/shortcut/manage/' . $new_set->id() . '/delete', [], t('Delete'));
    $sets = ShortcutSet::loadMultiple();
    $this->assertFalse(isset($sets[$new_set->id()]), 'Successfully deleted a shortcut set.');
  }

  /**
   * Tests deleting the default shortcut set.
   */
  public function testShortcutSetDeleteDefault() {
    $this->drupalGet('admin/config/user-interface/shortcut/manage/default/delete');
    $this->assertResponse(403);
  }

  /**
   * Tests creating a new shortcut set with a defined set name.
   */
  public function testShortcutSetCreateWithSetName() {
    $random_name = $this->randomMachineName();
    $new_set = $this->generateShortcutSet($random_name, $random_name);
    $sets = ShortcutSet::loadMultiple();
    $this->assertTrue(isset($sets[$random_name]), 'Successfully created a shortcut set with a defined set name.');
    $this->drupalGet('user/' . $this->adminUser->id() . '/shortcuts');
    $this->assertText($new_set->label(), 'Generated shortcut set was listed as a choice on the user account page.');
  }

}
