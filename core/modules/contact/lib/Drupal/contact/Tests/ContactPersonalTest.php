<?php

/**
 * @file
 * Definition of Drupal\contact\Tests\ContactPersonalTest.
 */

namespace Drupal\contact\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the personal contact form.
 */
class ContactPersonalTest extends WebTestBase {
  private $admin_user;
  private $web_user;
  private $contact_user;

  public static function getInfo() {
    return array(
      'name' => 'Personal contact form',
      'description' => 'Tests personal contact form functionality.',
      'group' => 'Contact',
    );
  }

  function setUp() {
    parent::setUp('contact');

    // Create an admin user.
    $this->admin_user = $this->drupalCreateUser(array('administer contact forms', 'administer users'));

    // Create some normal users with their contact forms enabled by default.
    variable_set('contact_default_status', TRUE);
    $this->web_user = $this->drupalCreateUser(array('access user contact forms'));
    $this->contact_user = $this->drupalCreateUser();
  }

  /**
   * Tests access to the personal contact form.
   */
  function testPersonalContactAccess() {
    // Test allowed access to admin user's contact form.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('user/' . $this->admin_user->uid . '/contact');
    $this->assertResponse(200);

    // Test denied access to admin user's own contact form.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('user/' . $this->admin_user->uid . '/contact');
    $this->assertResponse(403);

    // Test allowed access to user with contact form enabled.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('user/' . $this->contact_user->uid . '/contact');
    $this->assertResponse(200);

    // Test denied access to the user's own contact form.
    $this->drupalGet('user/' . $this->web_user->uid . '/contact');
    $this->assertResponse(403);

    // Test always denied access to the anonymous user contact form.
    $this->drupalGet('user/0/contact');
    $this->assertResponse(403);

    // Test that anonymous users can access the contact form.
    $this->drupalLogout();
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access user contact forms'));
    $this->drupalGet('user/' . $this->contact_user->uid . '/contact');
    $this->assertResponse(200);

    // Test that anonymous users can access admin user's contact form.
    $this->drupalGet('user/' . $this->admin_user->uid . '/contact');
    $this->assertResponse(200);

    // Revoke the personal contact permission for the anonymous user.
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('access user contact forms'));
    $this->drupalGet('user/' . $this->contact_user->uid . '/contact');
    $this->assertResponse(403);
    $this->drupalGet('user/' . $this->admin_user->uid . '/contact');
    $this->assertResponse(403);

    // Disable the personal contact form.
    $this->drupalLogin($this->admin_user);
    $edit = array('contact_default_status' => FALSE);
    $this->drupalPost('admin/config/people/accounts', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), t('Setting successfully saved.'));
    $this->drupalLogout();

    // Re-create our contacted user with personal contact forms disabled by
    // default.
    $this->contact_user = $this->drupalCreateUser();

    // Test denied access to a user with contact form disabled.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('user/' . $this->contact_user->uid . '/contact');
    $this->assertResponse(403);

    // Test allowed access for admin user to a user with contact form disabled.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('user/' . $this->contact_user->uid . '/contact');
    $this->assertResponse(200);

    // Re-create our contacted user as a blocked user.
    $this->contact_user = $this->drupalCreateUser();
    $this->contact_user->status = 0;
    $this->contact_user->save();

    // Test that blocked users can still be contacted by admin.
    $this->drupalGet('user/' . $this->contact_user->uid . '/contact');
    $this->assertResponse(200);

    // Test that blocked users cannot be contacted by non-admins.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('user/' . $this->contact_user->uid . '/contact');
    $this->assertResponse(403);
  }

  /**
   * Tests the personal contact form flood protection.
   */
  function testPersonalContactFlood() {
    $flood_limit = 3;
    variable_set('contact_threshold_limit', $flood_limit);

    // Clear flood table in preparation for flood test and allow other checks to complete.
    db_delete('flood')->execute();
    $num_records_flood = db_query("SELECT COUNT(*) FROM {flood}")->fetchField();
    $this->assertIdentical($num_records_flood, '0', 'Flood table emptied.');

    $this->drupalLogin($this->web_user);

    // Submit contact form with correct values and check flood interval.
    for ($i = 0; $i < $flood_limit; $i++) {
      $this->submitPersonalContact($this->contact_user);
      $this->assertText(t('Your message has been sent.'), 'Message sent.');
    }

    // Submit contact form one over limit.
    $this->drupalGet('user/' . $this->contact_user->uid. '/contact');
    $this->assertRaw(t('You cannot send more than %number messages in @interval. Try again later.', array('%number' => $flood_limit, '@interval' => format_interval(variable_get('contact_threshold_window', 3600)))), 'Normal user denied access to flooded contact form.');

    // Test that the admin user can still access the contact form even though
    // the flood limit was reached.
    $this->drupalLogin($this->admin_user);
    $this->assertNoText('Try again later.', 'Admin user not denied access to flooded contact form.');
  }

  /**
   * Fills out a user's personal contact form and submits it.
   *
   * @param $account
   *   A user object of the user being contacted.
   * @param $message
   *   An optional array with the form fields being used.
   */
  protected function submitPersonalContact($account, array $message = array()) {
    $message += array(
      'subject' => $this->randomName(16),
      'message' => $this->randomName(64),
    );
    $this->drupalPost('user/' . $account->uid . '/contact', $message, t('Send message'));
  }
}
