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

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contact');

  public static function getInfo() {
    return array(
      'name' => 'Site-wide contact form',
      'description' => 'Tests site-wide contact form functionality.',
      'group' => 'Contact',
    );
  }

  /**
   * Tests configuration options and the site-wide contact form.
   */
  function testSiteWideContact() {
    // Create and login administrative user.
    $admin_user = $this->drupalCreateUser(array('access site-wide contact form', 'administer contact forms', 'administer users'));
    $this->drupalLogin($admin_user);

    $flood_limit = 3;
    config('contact.settings')
      ->set('flood.limit', $flood_limit)
      ->set('flood.interval', 600)
      ->save();

    // Set settings.
    $edit = array();
    $edit['contact_default_status'] = TRUE;
    $this->drupalPost('admin/config/people/accounts', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'Setting successfully saved.');

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
      $this->addCategory($this->randomName(16), $this->randomName(16), $invalid_recipient, '', FALSE);
      $this->assertRaw(t('%recipient is an invalid e-mail address.', array('%recipient' => $invalid_recipient)), format_string('Caught invalid recipient (@invalid_recipient)', array('@invalid_recipient' => $invalid_recipient)));
    }

    // Test validation of empty category and recipients fields.
    $this->addCategory('', '', '', '', TRUE);
    $this->assertText(t('Label field is required.'), 'Caught empty category label field');
    $this->assertText(t('Machine-readable name field is required.'), 'Caught empty category name field');
    $this->assertText(t('Recipients field is required.'), 'Caught empty recipients field.');

    // Create first valid category.
    $recipients = array('simpletest@example.com', 'simpletest2@example.com', 'simpletest3@example.com');
    $this->addCategory($id = drupal_strtolower($this->randomName(16)), $label = $this->randomName(16), implode(',', array($recipients[0])), '', TRUE);
    $this->assertRaw(t('Category %label has been added.', array('%label' => $label)), 'Category successfully added.');

    // Make sure the newly created category is included in the list of categories.
    $this->assertNoUniqueText($label, 'New category included in categories list.');

    // Test update contact form category.
    $this->updateCategory($id, $label = $this->randomName(16), $recipients_str = implode(',', array($recipients[0], $recipients[1])), $reply = $this->randomName(30), FALSE);
    $config = config('contact.category.' . $id)->get();
    $this->assertEqual($config['label'], $label);
    $this->assertEqual($config['recipients'], array($recipients[0], $recipients[1]));
    $this->assertEqual($config['reply'], $reply);
    $this->assertNotEqual($id, config('contact.settings')->get('default_category'));
    $this->assertRaw(t('Category %label has been updated.', array('%label' => $label)), 'Category successfully updated.');

    // Ensure that the contact form is shown without a category selection input.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalLogout();
    $this->drupalGet('contact');
    $this->assertText(t('Your e-mail address'), 'Contact form is shown when there is one category.');
    $this->assertNoText(t('Category'), 'When there is only one category, the category selection element is hidden.');
    $this->drupalLogin($admin_user);

    // Add more categories.
    $this->addCategory(drupal_strtolower($this->randomName(16)), $label = $this->randomName(16), implode(',', array($recipients[0], $recipients[1])), '', FALSE);
    $this->assertRaw(t('Category %label has been added.', array('%label' => $label)), 'Category successfully added.');

    $this->addCategory($name = drupal_strtolower($this->randomName(16)), $label = $this->randomName(16), implode(',', array($recipients[0], $recipients[1], $recipients[2])), '', FALSE);
    $this->assertRaw(t('Category %label has been added.', array('%label' => $label)), 'Category successfully added.');

    // Try adding a category that already exists.
    $this->addCategory($name, $label, '', '', FALSE);
    $this->assertNoRaw(t('Category %label has been saved.', array('%label' => $label)), 'Category not saved.');
    $this->assertRaw(t('The machine-readable name is already in use. It must be unique.'), 'Duplicate category error found.');

    // Clear flood table in preparation for flood test and allow other checks to complete.
    db_delete('flood')->execute();
    $num_records_after = db_query("SELECT COUNT(*) FROM {flood}")->fetchField();
    $this->assertIdentical($num_records_after, '0', 'Flood table emptied.');
    $this->drupalLogout();

    // Check to see that anonymous user cannot see contact page without permission.
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalGet('contact');
    $this->assertResponse(403, 'Access denied to anonymous user without permission.');

    // Give anonymous user permission and see that page is viewable.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalGet('contact');
    $this->assertResponse(200, 'Access granted to anonymous user with permission.');

    // Submit contact form with invalid values.
    $categories = entity_load_multiple('contact_category');
    $id = key($categories);

    $this->submitContact('', $recipients[0], $this->randomName(16), $id, $this->randomName(64));
    $this->assertText(t('Your name field is required.'), 'Name required.');

    $this->submitContact($this->randomName(16), '', $this->randomName(16), $id, $this->randomName(64));
    $this->assertText(t('Your e-mail address field is required.'), 'E-mail required.');

    $this->submitContact($this->randomName(16), $invalid_recipients[0], $this->randomName(16), $id, $this->randomName(64));
    $this->assertRaw(t('The e-mail address %mail is not valid.', array('%mail' => 'invalid')), 'Valid e-mail required.');

    $this->submitContact($this->randomName(16), $recipients[0], '', $id, $this->randomName(64));
    $this->assertText(t('Subject field is required.'), 'Subject required.');

    $this->submitContact($this->randomName(16), $recipients[0], $this->randomName(16), $id, '');
    $this->assertText(t('Message field is required.'), 'Message required.');

    // Test contact form with no default category selected.
    config('contact.settings')
      ->set('default_category', '')
      ->save();
    $this->drupalGet('contact');
    $this->assertRaw(t('- Select -'), 'Without selected categories the visitor is asked to chose a category.');

    // Submit contact form with invalid category id (cid 0).
    $this->submitContact($this->randomName(16), $recipients[0], $this->randomName(16), 0, '');
    $this->assertText(t('Category field is required.'), 'Valid category required.');

    // Submit contact form with correct values and check flood interval.
    for ($i = 0; $i < $flood_limit; $i++) {
      $this->submitContact($this->randomName(16), $recipients[0], $this->randomName(16), $id, $this->randomName(64));
      $this->assertText(t('Your message has been sent.'), 'Message sent.');
    }
    // Submit contact form one over limit.
    $this->drupalGet('contact');
    $this->assertResponse(403, 'Access denied to anonymous user after reaching message treshold.');
    $this->assertRaw(t('You cannot send more than %number messages in @interval. Try again later.', array('%number' => config('contact.settings')->get('flood.limit'), '@interval' => format_interval(600))), 'Message threshold reached.');

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
    $this->addCategory('foo', 'foo', 'foo@example.com', $foo_autoreply, FALSE);
    $this->addCategory('bar', 'bar', 'bar@example.com', $bar_autoreply, FALSE);
    $this->addCategory('no_autoreply', 'no_autoreply', 'bar@example.com', '', FALSE);

    // Log the current user out in order to test the name and e-mail fields.
    $this->drupalLogout();
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));

    // Test the auto-reply for category 'foo'.
    $email = $this->randomName(32) . '@example.com';
    $subject = $this->randomName(64);
    $this->submitContact($this->randomName(16), $email, $subject, 'foo', $this->randomString(128));

    // We are testing the auto-reply, so there should be one e-mail going to the sender.
    $captured_emails = $this->drupalGetMails(array('id' => 'contact_page_autoreply', 'to' => $email, 'from' => 'foo@example.com'));
    $this->assertEqual(count($captured_emails), 1);
    $this->assertEqual($captured_emails[0]['body'], drupal_html_to_text($foo_autoreply));

    // Test the auto-reply for category 'bar'.
    $email = $this->randomName(32) . '@example.com';
    $this->submitContact($this->randomName(16), $email, $this->randomString(64), 'bar', $this->randomString(128));

    // Auto-reply for category 'bar' should result in one auto-reply e-mail to the sender.
    $captured_emails = $this->drupalGetMails(array('id' => 'contact_page_autoreply', 'to' => $email, 'from' => 'bar@example.com'));
    $this->assertEqual(count($captured_emails), 1);
    $this->assertEqual($captured_emails[0]['body'], drupal_html_to_text($bar_autoreply));

    // Verify that no auto-reply is sent when the auto-reply field is left blank.
    $email = $this->randomName(32) . '@example.com';
    $this->submitContact($this->randomName(16), $email, $this->randomString(64), 'no_autoreply', $this->randomString(128));
    $captured_emails = $this->drupalGetMails(array('id' => 'contact_page_autoreply', 'to' => $email, 'from' => 'no_autoreply@example.com'));
    $this->assertEqual(count($captured_emails), 0);
  }

  /**
   * Adds a category.
   *
   * @param string $id
   *   The category machine name.
   * @param string $label
   *   The category label.
   * @param string $recipients
   *   The list of recipient e-mail addresses.
   * @param string $reply
   *   The auto-reply text that is sent to a user upon completing the contact
   *   form.
   * @param boolean $selected
   *   Boolean indicating whether the category should be selected by default.
   */
  function addCategory($id, $label, $recipients, $reply, $selected) {
    $edit = array();
    $edit['label'] = $label;
    $edit['id'] = $id;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $this->drupalPost('admin/structure/contact/add', $edit, t('Save'));
  }

  /**
   * Updates a category.
   *
   * @param string $id
   *   The category machine name.
   * @param string $label
   *   The category label.
   * @param string $recipients
   *   The list of recipient e-mail addresses.
   * @param string $reply
   *   The auto-reply text that is sent to a user upon completing the contact
   *   form.
   * @param boolean $selected
   *   Boolean indicating whether the category should be selected by default.
   */
  function updateCategory($id, $label, $recipients, $reply, $selected) {
    $edit = array();
    $edit['label'] = $label;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $this->drupalPost("admin/structure/contact/manage/$id/edit", $edit, t('Save'));
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
   * @param string $id
   *   The category ID of the message.
   * @param string $message
   *   The message body.
   */
  function submitContact($name, $mail, $subject, $id, $message) {
    $edit = array();
    $edit['name'] = $name;
    $edit['mail'] = $mail;
    $edit['subject'] = $subject;
    $edit['category'] = $id;
    $edit['message'] = $message;
    $this->drupalPost('contact', $edit, t('Send message'));
  }

  /**
   * Deletes all categories.
   */
  function deleteCategories() {
    $categories = entity_load_multiple('contact_category');
    foreach ($categories as $id => $category) {
      $this->drupalPost("admin/structure/contact/manage/$id/delete", array(), t('Delete'));
      $this->assertRaw(t('Category %label has been deleted.', array('%label' => $category->label())), 'Category deleted successfully.');
      $this->assertFalse(entity_load('contact_category', $id), format_string('Category %category not found', array('%category' => $category->label())));
    }
  }

}
