<?php

/**
 * @file
 * Definition of Drupal\contact\Tests\ContactSitewideTest.
 */

namespace Drupal\contact\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the site-wide contact form.
 */
class ContactSitewideTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Site-wide contact form',
      'description' => 'Tests site-wide contact form functionality.',
      'group' => 'Contact',
    );
  }

  function setUp() {
    parent::setUp('contact');
  }

  /**
   * Tests configuration options and the site-wide contact form.
   */
  function testSiteWideContact() {
    // Create and login administrative user.
    $admin_user = $this->drupalCreateUser(array('access site-wide contact form', 'administer contact forms', 'administer users'));
    $this->drupalLogin($admin_user);

    $flood_limit = 3;
    variable_set('contact_threshold_limit', $flood_limit);
    variable_set('contact_threshold_window', 600);

    // Set settings.
    $edit = array();
    $edit['contact_default_status'] = TRUE;
    $this->drupalPost('admin/config/people/accounts', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), t('Setting successfully saved.'));

    // Delete old categories to ensure that new categories are used.
    $this->deleteCategories();

    // Ensure that the contact form won't be shown without categories.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalLogout();
    $this->drupalGet('contact');
    $this->assertResponse(404);
    $this->drupalLogin($admin_user);
    $this->drupalGet('contact');
    $this->assertResponse(200);
    $this->assertText(t('The contact form has not been configured.'));

    // Add categories.
    // Test invalid recipients.
    $invalid_recipients = array('invalid', 'invalid@', 'invalid@site.', '@site.', '@site.com');
    foreach ($invalid_recipients as $invalid_recipient) {
      $this->addCategory($this->randomName(16), $invalid_recipient, '', FALSE);
      $this->assertRaw(t('%recipient is an invalid e-mail address.', array('%recipient' => $invalid_recipient)), t('Caught invalid recipient (' . $invalid_recipient . ').'));
    }

    // Test validation of empty category and recipients fields.
    $this->addCategory($category = '', '', '', TRUE);
    $this->assertText(t('Category field is required.'), t('Caught empty category field'));
    $this->assertText(t('Recipients field is required.'), t('Caught empty recipients field.'));

    // Create first valid category.
    $recipients = array('simpletest@example.com', 'simpletest2@example.com', 'simpletest3@example.com');
    $this->addCategory($category = $this->randomName(16), implode(',', array($recipients[0])), '', TRUE);
    $this->assertRaw(t('Category %category has been saved.', array('%category' => $category)), t('Category successfully saved.'));

    // Make sure the newly created category is included in the list of categories.
    $this->assertNoUniqueText($category, t('New category included in categories list.'));

    // Test update contact form category.
    $categories = $this->getCategories();
    $category_id = $this->updateCategory($categories, $category = $this->randomName(16), $recipients_str = implode(',', array($recipients[0], $recipients[1])), $reply = $this->randomName(30), FALSE);
    $category_array = db_query("SELECT category, recipients, reply, selected FROM {contact} WHERE cid = :cid", array(':cid' => $category_id))->fetchAssoc();
    $this->assertEqual($category_array['category'], $category);
    $this->assertEqual($category_array['recipients'], $recipients_str);
    $this->assertEqual($category_array['reply'], $reply);
    $this->assertFalse($category_array['selected']);
    $this->assertRaw(t('Category %category has been saved.', array('%category' => $category)), t('Category successfully saved.'));

    // Ensure that the contact form is shown without a category selection input.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalLogout();
    $this->drupalGet('contact');
    $this->assertText(t('Your e-mail address'), t('Contact form is shown when there is one category.'));
    $this->assertNoText(t('Category'), t('When there is only one category, the category selection element is hidden.'));
    $this->drupalLogin($admin_user);

    // Add more categories.
    $this->addCategory($category = $this->randomName(16), implode(',', array($recipients[0], $recipients[1])), '', FALSE);
    $this->assertRaw(t('Category %category has been saved.', array('%category' => $category)), t('Category successfully saved.'));

    $this->addCategory($category = $this->randomName(16), implode(',', array($recipients[0], $recipients[1], $recipients[2])), '', FALSE);
    $this->assertRaw(t('Category %category has been saved.', array('%category' => $category)), t('Category successfully saved.'));

    // Try adding a category that already exists.
    $this->addCategory($category, '', '', FALSE);
    $this->assertNoRaw(t('Category %category has been saved.', array('%category' => $category)), t('Category not saved.'));
    $this->assertRaw(t('A contact form with category %category already exists.', array('%category' => $category)), t('Duplicate category error found.'));

    // Clear flood table in preparation for flood test and allow other checks to complete.
    db_delete('flood')->execute();
    $num_records_after = db_query("SELECT COUNT(*) FROM {flood}")->fetchField();
    $this->assertIdentical($num_records_after, '0', t('Flood table emptied.'));
    $this->drupalLogout();

    // Check to see that anonymous user cannot see contact page without permission.
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalGet('contact');
    $this->assertResponse(403, t('Access denied to anonymous user without permission.'));

    // Give anonymous user permission and see that page is viewable.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalGet('contact');
    $this->assertResponse(200, t('Access granted to anonymous user with permission.'));

    // Submit contact form with invalid values.
    $this->submitContact('', $recipients[0], $this->randomName(16), $categories[0], $this->randomName(64));
    $this->assertText(t('Your name field is required.'), t('Name required.'));

    $this->submitContact($this->randomName(16), '', $this->randomName(16), $categories[0], $this->randomName(64));
    $this->assertText(t('Your e-mail address field is required.'), t('E-mail required.'));

    $this->submitContact($this->randomName(16), $invalid_recipients[0], $this->randomName(16), $categories[0], $this->randomName(64));
    $this->assertRaw(t('The e-mail address %mail is not valid.', array('%mail' => 'invalid')), 'Valid e-mail required.');

    $this->submitContact($this->randomName(16), $recipients[0], '', $categories[0], $this->randomName(64));
    $this->assertText(t('Subject field is required.'), t('Subject required.'));

    $this->submitContact($this->randomName(16), $recipients[0], $this->randomName(16), $categories[0], '');
    $this->assertText(t('Message field is required.'), t('Message required.'));

    // Test contact form with no default category selected.
    db_update('contact')
      ->fields(array('selected' => 0))
      ->execute();
    $this->drupalGet('contact');
    $this->assertRaw(t('- Please choose -'), t('Without selected categories the visitor is asked to chose a category.'));

    // Submit contact form with invalid category id (cid 0).
    $this->submitContact($this->randomName(16), $recipients[0], $this->randomName(16), 0, '');
    $this->assertText(t('You must select a valid category.'), t('Valid category required.'));

    // Submit contact form with correct values and check flood interval.
    for ($i = 0; $i < $flood_limit; $i++) {
      $this->submitContact($this->randomName(16), $recipients[0], $this->randomName(16), $categories[0], $this->randomName(64));
      $this->assertText(t('Your message has been sent.'), t('Message sent.'));
    }
    // Submit contact form one over limit.
    $this->drupalGet('contact');
    $this->assertResponse(403, t('Access denied to anonymous user after reaching message treshold.'));
    $this->assertRaw(t('You cannot send more than %number messages in @interval. Try again later.', array('%number' => variable_get('contact_threshold_limit', 3), '@interval' => format_interval(600))), t('Message threshold reached.'));

    // Delete created categories.
    $this->drupalLogin($admin_user);
    $this->deleteCategories();
  }

  /**
  * Tests auto-reply on the site-wide contact form.
  */
  function testAutoReply() {
    // Create and login administrative user.
    $admin_user = $this->drupalCreateUser(array('access site-wide contact form', 'administer contact forms', 'administer permissions', 'administer users'));
    $this->drupalLogin($admin_user);

    // Set up three categories, 2 with an auto-reply and one without.
    $foo_autoreply = $this->randomName(40);
    $bar_autoreply = $this->randomName(40);
    $this->addCategory('foo', 'foo@example.com', $foo_autoreply, FALSE);
    $this->addCategory('bar', 'bar@example.com', $bar_autoreply, FALSE);
    $this->addCategory('no_autoreply', 'bar@example.com', '', FALSE);

    // Test the auto-reply for category 'foo'.
    $email = $this->randomName(32) . '@example.com';
    $subject = $this->randomName(64);
    $this->submitContact($this->randomName(16), $email, $subject, 2, $this->randomString(128));

    // We are testing the auto-reply, so there should be one e-mail going to the sender.
    $captured_emails = $this->drupalGetMails(array('id' => 'contact_page_autoreply', 'to' => $email, 'from' => 'foo@example.com'));
    $this->assertEqual(count($captured_emails), 1, t('Auto-reply e-mail was sent to the sender for category "foo".'), t('Contact'));
    $this->assertEqual($captured_emails[0]['body'], drupal_html_to_text($foo_autoreply), t('Auto-reply e-mail body is correct for category "foo".'), t('Contact'));

    // Test the auto-reply for category 'bar'.
    $email = $this->randomName(32) . '@example.com';
    $this->submitContact($this->randomName(16), $email, $this->randomString(64), 3, $this->randomString(128));

    // Auto-reply for category 'bar' should result in one auto-reply e-mail to the sender.
    $captured_emails = $this->drupalGetMails(array('id' => 'contact_page_autoreply', 'to' => $email, 'from' => 'bar@example.com'));
    $this->assertEqual(count($captured_emails), 1, t('Auto-reply e-mail was sent to the sender for category "bar".'), t('Contact'));
    $this->assertEqual($captured_emails[0]['body'], drupal_html_to_text($bar_autoreply), t('Auto-reply e-mail body is correct for category "bar".'), t('Contact'));

    // Verify that no auto-reply is sent when the auto-reply field is left blank.
    $email = $this->randomName(32) . '@example.com';
    $this->submitContact($this->randomName(16), $email, $this->randomString(64), 4, $this->randomString(128));
    $captured_emails = $this->drupalGetMails(array('id' => 'contact_page_autoreply', 'to' => $email, 'from' => 'no_autoreply@example.com'));
    $this->assertEqual(count($captured_emails), 0, t('No auto-reply e-mail was sent to the sender for category "no-autoreply".'), t('Contact'));
  }

  /**
   * Adds a category.
   *
   * @param string $category
   *   The category name.
   * @param string $recipients
   *   The list of recipient e-mail addresses.
   * @param string $reply
   *   The auto-reply text that is sent to a user upon completing the contact
   *   form.
   * @param boolean $selected
   *   Boolean indicating whether the category should be selected by default.
   */
  function addCategory($category, $recipients, $reply, $selected) {
    $edit = array();
    $edit['category'] = $category;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $this->drupalPost('admin/structure/contact/add', $edit, t('Save'));
  }

  /**
   * Updates a category.
   *
   * @param string $category
   *   The category name.
   * @param string $recipients
   *   The list of recipient e-mail addresses.
   * @param string $reply
   *   The auto-reply text that is sent to a user upon completing the contact
   *   form.
   * @param boolean $selected
   *   Boolean indicating whether the category should be selected by default.
   */
  function updateCategory($categories, $category, $recipients, $reply, $selected) {
    $category_id = $categories[array_rand($categories)];
    $edit = array();
    $edit['category'] = $category;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $this->drupalPost('admin/structure/contact/edit/' . $category_id, $edit, t('Save'));
    return ($category_id);
  }

  /**
   * Submits the contact form.
   *
   * @param string $name
   *   The name of the sender.
   * @param string $mail
   *   The e-mail address of the sender.
   * @param string $subject
   *   The subject of the message.
   * @param integer $cid
   *   The category ID of the message.
   * @param string $message
   *   The message body.
   */
  function submitContact($name, $mail, $subject, $cid, $message) {
    $edit = array();
    $edit['name'] = $name;
    $edit['mail'] = $mail;
    $edit['subject'] = $subject;
    $edit['cid'] = $cid;
    $edit['message'] = $message;
    $this->drupalPost('contact', $edit, t('Send message'));
  }

  /**
   * Deletes all categories.
   */
  function deleteCategories() {
    $categories = $this->getCategories();
    foreach ($categories as $category) {
      $category_name = db_query("SELECT category FROM {contact} WHERE cid = :cid", array(':cid' => $category))->fetchField();
      $this->drupalPost('admin/structure/contact/delete/' . $category, array(), t('Delete'));
      $this->assertRaw(t('Category %category has been deleted.', array('%category' => $category_name)), t('Category deleted successfully.'));
    }
  }

  /**
   * Gets a list of all category IDs.
   *
   * @return array
   *   A list of the category IDs.
   */
  function getCategories() {
    $categories = db_query('SELECT cid FROM {contact}')->fetchCol();
    return $categories;
  }
}
