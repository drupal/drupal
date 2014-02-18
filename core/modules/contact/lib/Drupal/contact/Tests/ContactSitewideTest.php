<?php

/**
 * @file
 * Definition of Drupal\contact\Tests\ContactSitewideTest.
 */

namespace Drupal\contact\Tests;

use Drupal\Component\Utility\Unicode;
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
  public static $modules = array('text', 'contact', 'field_ui');

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
    $admin_user = $this->drupalCreateUser(array(
      'access site-wide contact form',
      'administer contact forms',
      'administer users',
      'administer account settings',
      'administer contact_message fields',
    ));
    $this->drupalLogin($admin_user);

    $flood_limit = 3;
    \Drupal::config('contact.settings')
      ->set('flood.limit', $flood_limit)
      ->set('flood.interval', 600)
      ->save();

    // Set settings.
    $edit = array();
    $edit['contact_default_status'] = TRUE;
    $this->drupalPostForm('admin/config/people/accounts', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    $this->drupalGet('admin/structure/contact');
    // Default category exists.
    $this->assertLinkByHref('admin/structure/contact/manage/feedback/delete');
    // User category could not be changed or deleted.
    // Cannot use ::assertNoLinkByHref as it does partial url matching and with
    // field_ui enabled admin/structure/contact/manage/personal/fields exists.
    // @todo: See https://drupal.org/node/2031223 for the above
    $edit_link = $this->xpath('//a[@href=:href]', array(
      ':href' => url('admin/structure/contact/manage/personal')
    ));
    $this->assertTrue(empty($edit_link), format_string('No link containing href %href found.',
      array('%href' => 'admin/structure/contact/manage/personal')
    ));
    $this->assertNoLinkByHref('admin/structure/contact/manage/personal/delete');

    $this->drupalGet('admin/structure/contact/manage/personal');
    $this->assertResponse(403);

    // Delete old categories to ensure that new categories are used.
    $this->deleteCategories();
    $this->drupalGet('admin/structure/contact');
    $this->assertText('Personal', 'Personal category was not deleted');
    $this->assertNoLinkByHref('admin/structure/contact/manage/feedback');

    // Ensure that the contact form won't be shown without categories.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalLogout();
    $this->drupalGet('contact');
    $this->assertResponse(404);

    $this->drupalLogin($admin_user);
    $this->drupalGet('contact');
    $this->assertResponse(200);
    $this->assertText(t('The contact form has not been configured.'));
    // Test access personal category via site-wide contact page.
    $this->drupalGet('contact/personal');
    $this->assertResponse(403);

    // Add categories.
    // Test invalid recipients.
    $invalid_recipients = array('invalid', 'invalid@', 'invalid@site.', '@site.', '@site.com');
    foreach ($invalid_recipients as $invalid_recipient) {
      $this->addCategory($this->randomName(16), $this->randomName(16), $invalid_recipient, '', FALSE);
      $this->assertRaw(t('%recipient is an invalid e-mail address.', array('%recipient' => $invalid_recipient)));
    }

    // Test validation of empty category and recipients fields.
    $this->addCategory('', '', '', '', TRUE);
    $this->assertText(t('Label field is required.'));
    $this->assertText(t('Machine-readable name field is required.'));
    $this->assertText(t('Recipients field is required.'));

    // Create first valid category.
    $recipients = array('simpletest@example.com', 'simpletest2@example.com', 'simpletest3@example.com');
    $this->addCategory($id = drupal_strtolower($this->randomName(16)), $label = $this->randomName(16), implode(',', array($recipients[0])), '', TRUE);
    $this->assertRaw(t('Category %label has been added.', array('%label' => $label)));

    // Check that the category was created in site default language.
    $langcode = \Drupal::config('contact.category.' . $id)->get('langcode');
    $default_langcode = language_default()->id;
    $this->assertEqual($langcode, $default_langcode);

    // Make sure the newly created category is included in the list of categories.
    $this->assertNoUniqueText($label, 'New category included in categories list.');

    // Test update contact form category.
    $this->updateCategory($id, $label = $this->randomName(16), $recipients_str = implode(',', array($recipients[0], $recipients[1])), $reply = $this->randomName(30), FALSE);
    $config = \Drupal::config('contact.category.' . $id)->get();
    $this->assertEqual($config['label'], $label);
    $this->assertEqual($config['recipients'], array($recipients[0], $recipients[1]));
    $this->assertEqual($config['reply'], $reply);
    $this->assertNotEqual($id, \Drupal::config('contact.settings')->get('default_category'));
    $this->assertRaw(t('Category %label has been updated.', array('%label' => $label)));
    // Ensure the label is displayed on the contact page for this category.
    $this->drupalGet('contact/' . $id);
    $this->assertText($label);

    // Reset the category back to be the default category.
    \Drupal::config('contact.settings')->set('default_category', $id)->save();

    // Ensure that the contact form is shown without a category selection input.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalLogout();
    $this->drupalGet('contact');
    $this->assertText(t('Your e-mail address'));
    $this->assertNoText(t('Category'));
    $this->drupalLogin($admin_user);

    // Add more categories.
    $this->addCategory(drupal_strtolower($this->randomName(16)), $label = $this->randomName(16), implode(',', array($recipients[0], $recipients[1])), '', FALSE);
    $this->assertRaw(t('Category %label has been added.', array('%label' => $label)));

    $this->addCategory($name = drupal_strtolower($this->randomName(16)), $label = $this->randomName(16), implode(',', array($recipients[0], $recipients[1], $recipients[2])), '', FALSE);
    $this->assertRaw(t('Category %label has been added.', array('%label' => $label)));

    // Try adding a category that already exists.
    $this->addCategory($name, $label, '', '', FALSE);
    $this->assertNoRaw(t('Category %label has been saved.', array('%label' => $label)));
    $this->assertRaw(t('The machine-readable name is already in use. It must be unique.'));

    // Clear flood table in preparation for flood test and allow other checks to complete.
    db_delete('flood')->execute();
    $num_records_after = db_query("SELECT COUNT(*) FROM {flood}")->fetchField();
    $this->assertIdentical($num_records_after, '0', 'Flood table emptied.');
    $this->drupalLogout();

    // Check to see that anonymous user cannot see contact page without permission.
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalGet('contact');
    $this->assertResponse(403);

    // Give anonymous user permission and see that page is viewable.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalGet('contact');
    $this->assertResponse(200);

    // Submit contact form with invalid values.
    $this->submitContact('', $recipients[0], $this->randomName(16), $id, $this->randomName(64));
    $this->assertText(t('Your name field is required.'));

    $this->submitContact($this->randomName(16), '', $this->randomName(16), $id, $this->randomName(64));
    $this->assertText(t('Your e-mail address field is required.'));

    $this->submitContact($this->randomName(16), $invalid_recipients[0], $this->randomName(16), $id, $this->randomName(64));
    $this->assertRaw(t('The e-mail address %mail is not valid.', array('%mail' => 'invalid')));

    $this->submitContact($this->randomName(16), $recipients[0], '', $id, $this->randomName(64));
    $this->assertText(t('Subject field is required.'));

    $this->submitContact($this->randomName(16), $recipients[0], $this->randomName(16), $id, '');
    $this->assertText(t('Message field is required.'));

    // Test contact form with no default category selected.
    \Drupal::config('contact.settings')
      ->set('default_category', '')
      ->save();
    $this->drupalGet('contact');
    $this->assertResponse(404);

    // Try to access contact form with non-existing category IDs.
    $this->drupalGet('contact/0');
    $this->assertResponse(404);
    $this->drupalGet('contact/' . $this->randomName());
    $this->assertResponse(404);

    // Submit contact form with correct values and check flood interval.
    for ($i = 0; $i < $flood_limit; $i++) {
      $this->submitContact($this->randomName(16), $recipients[0], $this->randomName(16), $id, $this->randomName(64));
      $this->assertText(t('Your message has been sent.'));
    }
    // Submit contact form one over limit.
    $this->drupalGet('contact');
    $this->assertResponse(403);
    $this->assertRaw(t('You cannot send more than %number messages in @interval. Try again later.', array('%number' => \Drupal::config('contact.settings')->get('flood.limit'), '@interval' => format_interval(600))));

    // Test listing controller.
    $this->drupalLogin($admin_user);

    $this->deleteCategories();

    $label = $this->randomName(16);
    $recipients = implode(',', array($recipients[0], $recipients[1], $recipients[2]));
    $category = drupal_strtolower($this->randomName(16));
    $this->addCategory($category, $label, $recipients, '', FALSE);
    $this->drupalGet('admin/structure/contact');
    $this->clickLink(t('Edit'));
    $this->assertResponse(200);
    $this->assertFieldByName('label', $label);

    // Test field UI and field integration.
    $this->drupalGet('admin/structure/contact');

    // Find out in which row the category we want to add a field to is.
    $i = 0;
    foreach($this->xpath('//table/tbody/tr') as $row) {
      if (((string)$row->td[0]) == $label) {
        break;
      }
      $i++;
    }

    $this->clickLink(t('Manage fields'), $i);
    $this->assertResponse(200);

    // Create a simple textfield.
    $edit = array(
      'fields[_add_new_field][label]' => $field_label = $this->randomName(),
      'fields[_add_new_field][field_name]' => Unicode::strtolower($this->randomName()),
      'fields[_add_new_field][type]' => 'text',
    );
    $field_name = 'field_' . $edit['fields[_add_new_field][field_name]'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalPostForm(NULL, array(), t('Save field settings'));
    $this->drupalPostForm(NULL, array(), t('Save settings'));

    // Check that the field is displayed.
    $this->drupalGet('contact/' . $category);
    $this->assertText($field_label);

    // Submit the contact form and verify the content.
    $edit = array(
      'subject' => $this->randomName(),
      'message' => $this->randomName(),
      $field_name . '[0][value]' => $this->randomName(),
    );
    $this->drupalPostForm(NULL, $edit, t('Send message'));
    $mails = $this->drupalGetMails();
    $mail = array_pop($mails);
    $this->assertEqual($mail['subject'], t('[@label] @subject', array('@label' => $label, '@subject' => $edit['subject'])));
    $this->assertTrue(strpos($mail['body'], $field_label));
    $this->assertTrue(strpos($mail['body'], $edit[$field_name . '[0][value]']));
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
    $captured_emails = $this->drupalGetMails(array('id' => 'contact_page_autoreply', 'to' => $email));
    $this->assertEqual(count($captured_emails), 1);
    $this->assertEqual(trim($captured_emails[0]['body']), trim(drupal_html_to_text($foo_autoreply)));

    // Test the auto-reply for category 'bar'.
    $email = $this->randomName(32) . '@example.com';
    $this->submitContact($this->randomName(16), $email, $this->randomString(64), 'bar', $this->randomString(128));

    // Auto-reply for category 'bar' should result in one auto-reply e-mail to the sender.
    $captured_emails = $this->drupalGetMails(array('id' => 'contact_page_autoreply', 'to' => $email));
    $this->assertEqual(count($captured_emails), 1);
    $this->assertEqual(trim($captured_emails[0]['body']), trim(drupal_html_to_text($bar_autoreply)));

    // Verify that no auto-reply is sent when the auto-reply field is left blank.
    $email = $this->randomName(32) . '@example.com';
    $this->submitContact($this->randomName(16), $email, $this->randomString(64), 'no_autoreply', $this->randomString(128));
    $captured_emails = $this->drupalGetMails(array('id' => 'contact_page_autoreply', 'to' => $email));
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
   *   A Boolean indicating whether the category should be selected by default.
   */
  function addCategory($id, $label, $recipients, $reply, $selected) {
    $edit = array();
    $edit['label'] = $label;
    $edit['id'] = $id;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $this->drupalPostForm('admin/structure/contact/add', $edit, t('Save'));
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
   *   A Boolean indicating whether the category should be selected by default.
   */
  function updateCategory($id, $label, $recipients, $reply, $selected) {
    $edit = array();
    $edit['label'] = $label;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $this->drupalPostForm("admin/structure/contact/manage/$id", $edit, t('Save'));
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
    $edit['message'] = $message;
    if ($id == \Drupal::config('contact.settings')->get('default_category')) {
      $this->drupalPostForm('contact', $edit, t('Send message'));
    }
    else {
      $this->drupalPostForm('contact/' . $id, $edit, t('Send message'));
    }
  }

  /**
   * Deletes all categories.
   */
  function deleteCategories() {
    $categories = entity_load_multiple('contact_category');
    foreach ($categories as $id => $category) {
      if ($id == 'personal') {
        // Personal category could not be deleted.
        $this->drupalGet("admin/structure/contact/manage/$id/delete");
        $this->assertResponse(403);
      }
      else {
        $this->drupalPostForm("admin/structure/contact/manage/$id/delete", array(), t('Delete'));
        $this->assertRaw(t('Category %label has been deleted.', array('%label' => $category->label())));
        $this->assertFalse(entity_load('contact_category', $id), format_string('Category %category not found', array('%category' => $category->label())));
      }
    }
  }

}
