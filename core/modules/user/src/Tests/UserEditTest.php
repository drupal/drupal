<?php

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests user edit page.
 *
 * @group user
 */
class UserEditTest extends WebTestBase {

  /**
   * Test user edit page.
   */
  public function testUserEdit() {
    // Test user edit functionality.
    $user1 = $this->drupalCreateUser(['change own username']);
    $user2 = $this->drupalCreateUser([]);
    $this->drupalLogin($user1);

    // Test that error message appears when attempting to use a non-unique user name.
    $edit['name'] = $user2->getUsername();
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertRaw(t('The username %name is already taken.', ['%name' => $edit['name']]));

    // Check that the default value in user name field
    // is the raw value and not a formatted one.
    \Drupal::state()->set('user_hooks_test_user_format_name_alter', TRUE);
    \Drupal::service('module_installer')->install(['user_hooks_test']);
    $this->drupalGet('user/' . $user1->id() . '/edit');
    $this->assertFieldByName('name', $user1->getAccountName());

    // Check that filling out a single password field does not validate.
    $edit = [];
    $edit['pass[pass1]'] = '';
    $edit['pass[pass2]'] = $this->randomMachineName();
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertText(t("The specified passwords do not match."), 'Typing mismatched passwords displays an error message.');

    $edit['pass[pass1]'] = $this->randomMachineName();
    $edit['pass[pass2]'] = '';
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertText(t("The specified passwords do not match."), 'Typing mismatched passwords displays an error message.');

    // Test that the error message appears when attempting to change the mail or
    // pass without the current password.
    $edit = [];
    $edit['mail'] = $this->randomMachineName() . '@new.example.com';
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertRaw(t("Your current password is missing or incorrect; it's required to change the %name.", ['%name' => t('Email')]));

    $edit['current_pass'] = $user1->pass_raw;
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertRaw(t("The changes have been saved."));

    // Test that the user must enter current password before changing passwords.
    $edit = [];
    $edit['pass[pass1]'] = $new_pass = $this->randomMachineName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertRaw(t("Your current password is missing or incorrect; it's required to change the %name.", ['%name' => t('Password')]));

    // Try again with the current password.
    $edit['current_pass'] = $user1->pass_raw;
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertRaw(t("The changes have been saved."));

    // Make sure the changed timestamp is updated.
    $this->assertEqual($user1->getChangedTime(), REQUEST_TIME, 'Changing a user sets "changed" timestamp.');

    // Make sure the user can log in with their new password.
    $this->drupalLogout();
    $user1->pass_raw = $new_pass;
    $this->drupalLogin($user1);
    $this->drupalLogout();

    // Test that the password strength indicator displays.
    $config = $this->config('user.settings');
    $this->drupalLogin($user1);

    $config->set('password_strength', TRUE)->save();
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertRaw(t('Password strength:'), 'The password strength indicator is displayed.');

    $config->set('password_strength', FALSE)->save();
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertNoRaw(t('Password strength:'), 'The password strength indicator is not displayed.');

    // Check that the user status field has the correct value and that it is
    // properly displayed.
    $admin_user = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('user/' . $user1->id() . '/edit');
    $this->assertNoFieldChecked('edit-status-0');
    $this->assertFieldChecked('edit-status-1');

    $edit = ['status' => 0];
    $this->drupalPostForm('user/' . $user1->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('The changes have been saved.'));
    $this->assertFieldChecked('edit-status-0');
    $this->assertNoFieldChecked('edit-status-1');

    $edit = ['status' => 1];
    $this->drupalPostForm('user/' . $user1->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('The changes have been saved.'));
    $this->assertNoFieldChecked('edit-status-0');
    $this->assertFieldChecked('edit-status-1');
  }

  /**
   * Tests setting the password to "0".
   *
   * We discovered in https://www.drupal.org/node/2563751 that logging in with a
   * password that is literally "0" was not possible. This test ensures that
   * this regression can't happen again.
   */
  public function testUserWith0Password() {
    $admin = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($admin);
    // Create a regular user.
    $user1 = $this->drupalCreateUser([]);

    $edit = ['pass[pass1]' => '0', 'pass[pass2]' => '0'];
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertRaw(t("The changes have been saved."));
  }

  /**
   * Tests editing of a user account without an email address.
   */
  public function testUserWithoutEmailEdit() {
    // Test that an admin can edit users without an email address.
    $admin = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($admin);
    // Create a regular user.
    $user1 = $this->drupalCreateUser([]);
    // This user has no email address.
    $user1->mail = '';
    $user1->save();
    $this->drupalPostForm("user/" . $user1->id() . "/edit", ['mail' => ''], t('Save'));
    $this->assertRaw(t("The changes have been saved."));
  }

}
