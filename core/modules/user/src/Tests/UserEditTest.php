<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserEditTest.
 */

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
  function testUserEdit() {
    // Test user edit functionality.
    $user1 = $this->drupalCreateUser(array('change own username'));
    $user2 = $this->drupalCreateUser(array());
    $this->drupalLogin($user1);

    // Test that error message appears when attempting to use a non-unique user name.
    $edit['name'] = $user2->getUsername();
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertRaw(t('The username %name is already taken.', array('%name' => $edit['name'])));

    // Check that filling out a single password field does not validate.
    $edit = array();
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
    $edit = array();
    $edit['mail'] = $this->randomMachineName() . '@new.example.com';
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertRaw(t("Your current password is missing or incorrect; it's required to change the %name.", array('%name' => t('Email address'))));

    $edit['current_pass'] = $user1->pass_raw;
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertRaw(t("The changes have been saved."));

    // Test that the user must enter current password before changing passwords.
    $edit = array();
    $edit['pass[pass1]'] = $new_pass = $this->randomMachineName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));
    $this->assertRaw(t("Your current password is missing or incorrect; it's required to change the %name.", array('%name' => t('Password'))));

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
  }

  /**
   * Tests editing of a user account without an email address.
   */
  function testUserWithoutEmailEdit() {
    // Test that an admin can edit users without an email address.
    $admin = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($admin);
    // Create a regular user.
    $user1 = $this->drupalCreateUser(array());
    // This user has no email address.
    $user1->mail = '';
    $user1->save();
    $this->drupalPostForm("user/" . $user1->id() . "/edit", array('mail' => ''), t('Save'));
    $this->assertRaw(t("The changes have been saved."));
  }
}
