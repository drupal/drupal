<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Functional;

use Drupal\shortcut\Entity\ShortcutSet;

/**
 * Create, view, edit, delete, and change shortcut sets.
 *
 * @group shortcut
 * @group #slow
 */
class ShortcutSetsTest extends ShortcutTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests creating a shortcut set.
   */
  public function testShortcutSetAdd(): void {
    $this->drupalGet('admin/config/user-interface/shortcut');
    $this->clickLink('Add shortcut set');
    $edit = [
      'label' => $this->randomMachineName(),
      'id' => $this->randomMachineName(),
    ];
    $this->submitForm($edit, 'Save');
    $new_set = $this->container->get('entity_type.manager')->getStorage('shortcut_set')->load($edit['id']);
    $this->assertSame($edit['id'], $new_set->id(), 'Successfully created a shortcut set.');
    $this->drupalGet('user/' . $this->adminUser->id() . '/shortcuts');
    // Verify that generated shortcut set was listed as a choice on the user
    // account page.
    $this->assertSession()->pageTextContains($new_set->label());
  }

  /**
   * Tests editing a shortcut set.
   */
  public function testShortcutSetEdit(): void {
    $set = $this->set;
    $shortcuts = $set->getShortcuts();

    // Visit the shortcut set edit admin ui.
    $this->drupalGet('admin/config/user-interface/shortcut/manage/' . $set->id() . '/customize');

    // Test for the page title.
    $this->assertSession()->titleEquals('List links | Drupal');

    // Test for the table.
    $this->assertSession()->elementExists('xpath', '//div[@class="layout-content"]//table');

    // Test the table header.
    $this->assertSession()->elementsCount('xpath', '//div[@class="layout-content"]//table/thead/tr/th', 3);

    // Test the contents of each th cell.
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/thead/tr/th[1]', 'Name');
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/thead/tr/th[2]', 'Weight');
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/thead/tr/th[3]', 'Operations');

    // Look for test shortcuts in the table.
    $weight = count($shortcuts);
    $edit = [];
    foreach ($shortcuts as $shortcut) {
      $title = $shortcut->getTitle();

      // Confirm that a link to the shortcut is found within the table.
      $this->assertSession()->linkExists($title);

      // Look for a test shortcut weight select form element.
      $this->assertSession()->fieldExists('shortcuts[links][' . $shortcut->id() . '][weight]');

      // Change the weight of the shortcut.
      $edit['shortcuts[links][' . $shortcut->id() . '][weight]'] = $weight;
      $weight--;
    }

    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("The shortcut set has been updated.");

    \Drupal::entityTypeManager()->getStorage('shortcut')->resetCache();
    // Check to ensure that the shortcut weights have changed and that
    // ShortcutSet::.getShortcuts() returns shortcuts in the new order.
    $this->assertSame(array_reverse(array_keys($shortcuts)), array_keys($set->getShortcuts()));
  }

  /**
   * Tests switching a user's own shortcut set.
   */
  public function testShortcutSetSwitchOwn(): void {
    $new_set = $this->generateShortcutSet($this->randomMachineName());

    // Attempt to switch the default shortcut set to the newly created shortcut
    // set.
    $this->drupalGet('user/' . $this->adminUser->id() . '/shortcuts');
    $this->submitForm(['set' => $new_set->id()], 'Change set');
    $this->assertSession()->statusCodeEquals(200);
    $shortcut_set_storage = \Drupal::entityTypeManager()->getStorage('shortcut_set');
    $current_set = $shortcut_set_storage->getDisplayedToUser($this->adminUser);
    $this->assertSame($current_set->id(), $new_set->id(), 'Successfully switched own shortcut set.');
  }

  /**
   * Tests switching another user's shortcut set.
   */
  public function testShortcutSetAssign(): void {
    $new_set = $this->generateShortcutSet($this->randomMachineName());

    $shortcut_set_storage = \Drupal::entityTypeManager()->getStorage('shortcut_set');
    $shortcut_set_storage->assignUser($new_set, $this->shortcutUser);
    $current_set = $shortcut_set_storage->getDisplayedToUser($this->shortcutUser);
    $this->assertSame($current_set->id(), $new_set->id(), "Successfully switched another user's shortcut set.");
  }

  /**
   * Tests switching a user's shortcut set and creating one at the same time.
   */
  public function testShortcutSetSwitchCreate(): void {
    $edit = [
      'set' => 'new',
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ];
    $this->drupalGet('user/' . $this->adminUser->id() . '/shortcuts');
    $this->submitForm($edit, 'Change set');
    $shortcut_set_storage = \Drupal::entityTypeManager()->getStorage('shortcut_set');
    $current_set = $shortcut_set_storage->getDisplayedToUser($this->adminUser);
    $this->assertNotEquals($this->set->id(), $current_set->id(), 'A shortcut set can be switched to at the same time as it is created.');
    $this->assertEquals($edit['label'], $current_set->label(), 'The new set is correctly assigned to the user.');
  }

  /**
   * Tests switching a user's shortcut set without providing a new set name.
   */
  public function testShortcutSetSwitchNoSetName(): void {
    $edit = ['set' => 'new'];
    $this->drupalGet('user/' . $this->adminUser->id() . '/shortcuts');
    $this->submitForm($edit, 'Change set');
    $this->assertSession()->pageTextContains('The new set label is required.');
    $shortcut_set_storage = \Drupal::entityTypeManager()->getStorage('shortcut_set');
    $current_set = $shortcut_set_storage->getDisplayedToUser($this->adminUser);
    $this->assertEquals($this->set->id(), $current_set->id(), 'Attempting to switch to a new shortcut set without providing a set name does not succeed.');
    $field = $this->assertSession()->fieldExists('label');
    $this->assertTrue($field->hasClass('error'));
  }

  /**
   * Tests renaming a shortcut set.
   */
  public function testShortcutSetRename(): void {
    $set = $this->set;

    $new_label = $this->randomMachineName();
    $this->drupalGet('admin/config/user-interface/shortcut');
    $this->clickLink('Edit shortcut set');
    $this->submitForm(['label' => $new_label], 'Save');
    $set = ShortcutSet::load($set->id());
    $this->assertSame($new_label, $set->label(), 'Shortcut set has been successfully renamed.');
  }

  /**
   * Tests un-assigning a shortcut set.
   */
  public function testShortcutSetUnassign(): void {
    $new_set = $this->generateShortcutSet($this->randomMachineName());

    $shortcut_set_storage = \Drupal::entityTypeManager()->getStorage('shortcut_set');
    $shortcut_set_storage->assignUser($new_set, $this->shortcutUser);
    $shortcut_set_storage->unassignUser($this->shortcutUser);
    $current_set = $shortcut_set_storage->getDisplayedToUser($this->shortcutUser);
    $default_set = $shortcut_set_storage->getDefaultSet($this->shortcutUser);
    $this->assertSame($default_set->id(), $current_set->id(), "Successfully unassigned another user's shortcut set.");
  }

  /**
   * Tests assign clearing on user removal.
   */
  public function testShortcutSetUnassignOnUserRemoval(): void {
    $new_set = $this->generateShortcutSet($this->randomMachineName());

    $shortcut_set_storage = \Drupal::entityTypeManager()->getStorage('shortcut_set');
    $shortcut_set_storage->assignUser($new_set, $this->shortcutUser);
    $this->shortcutUser->delete();
    $current_set = $shortcut_set_storage->getDisplayedToUser($this->shortcutUser);
    $default_set = $shortcut_set_storage->getDefaultSet($this->shortcutUser);
    $this->assertSame($default_set->id(), $current_set->id(), "Successfully cleared assigned shortcut set for removed user.");
  }

  /**
   * Tests deleting a shortcut set.
   */
  public function testShortcutSetDelete(): void {
    $new_set = $this->generateShortcutSet($this->randomMachineName());

    $this->drupalGet('admin/config/user-interface/shortcut/manage/' . $new_set->id() . '/delete');
    $this->submitForm([], 'Delete');
    $sets = ShortcutSet::loadMultiple();
    $this->assertFalse(isset($sets[$new_set->id()]), 'Successfully deleted a shortcut set.');
  }

  /**
   * Tests deleting the default shortcut set.
   */
  public function testShortcutSetDeleteDefault(): void {
    $this->drupalGet('admin/config/user-interface/shortcut/manage/default/delete');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests creating a new shortcut set with a defined set name.
   */
  public function testShortcutSetCreateWithSetName(): void {
    $random_name = $this->randomMachineName();
    $new_set = $this->generateShortcutSet($random_name, $random_name);
    $sets = ShortcutSet::loadMultiple();
    $this->assertTrue(isset($sets[$random_name]), 'Successfully created a shortcut set with a defined set name.');
    $this->drupalGet('user/' . $this->adminUser->id() . '/shortcuts');
    // Verify that generated shortcut set was listed as a choice on the user
    // account page.
    $this->assertSession()->pageTextContains($new_set->label());
  }

}
