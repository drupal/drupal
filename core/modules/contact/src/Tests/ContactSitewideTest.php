<?php

/**
 * @file
 * Contains \Drupal\contact\Tests\ContactSitewideTest.
 */

namespace Drupal\contact\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Tests site-wide contact form functionality.
 *
 * @group contact
 */
class ContactSitewideTest extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('text', 'contact', 'field_ui', 'contact_test');

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
    $this->config('contact.settings')
      ->set('flood.limit', $flood_limit)
      ->set('flood.interval', 600)
      ->save();

    // Set settings.
    $edit = array();
    $edit['contact_default_status'] = TRUE;
    $this->drupalPostForm('admin/config/people/accounts', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    $this->drupalGet('admin/structure/contact');
    // Default form exists.
    $this->assertLinkByHref('admin/structure/contact/manage/feedback/delete');
    // User form could not be changed or deleted.
    // Cannot use ::assertNoLinkByHref as it does partial url matching and with
    // field_ui enabled admin/structure/contact/manage/personal/fields exists.
    // @todo: See https://drupal.org/node/2031223 for the above
    $edit_link = $this->xpath('//a[@href=:href]', array(
      ':href' => \Drupal::url('entity.contact_form.edit_form', array('contact_form' => 'personal'))
    ));
    $this->assertTrue(empty($edit_link), format_string('No link containing href %href found.',
      array('%href' => 'admin/structure/contact/manage/personal')
    ));
    $this->assertNoLinkByHref('admin/structure/contact/manage/personal/delete');

    $this->drupalGet('admin/structure/contact/manage/personal');
    $this->assertResponse(403);

    // Delete old forms to ensure that new forms are used.
    $this->deleteContactForms();
    $this->drupalGet('admin/structure/contact');
    $this->assertText('Personal', 'Personal form was not deleted');
    $this->assertNoLinkByHref('admin/structure/contact/manage/feedback');

    // Ensure that the contact form won't be shown without forms.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalLogout();
    $this->drupalGet('contact');
    $this->assertResponse(404);

    $this->drupalLogin($admin_user);
    $this->drupalGet('contact');
    $this->assertResponse(200);
    $this->assertText(t('The contact form has not been configured.'));
    // Test access personal form via site-wide contact page.
    $this->drupalGet('contact/personal');
    $this->assertResponse(403);

    // Add forms.
    // Test invalid recipients.
    $invalid_recipients = array('invalid', 'invalid@', 'invalid@site.', '@site.', '@site.com');
    foreach ($invalid_recipients as $invalid_recipient) {
      $this->addContactForm($this->randomMachineName(16), $this->randomMachineName(16), $invalid_recipient, '', FALSE);
      $this->assertRaw(t('%recipient is an invalid email address.', array('%recipient' => $invalid_recipient)));
    }

    // Test validation of empty form and recipients fields.
    $this->addContactForm('', '', '', '', TRUE);
    $this->assertText(t('Label field is required.'));
    $this->assertText(t('Machine-readable name field is required.'));
    $this->assertText(t('Recipients field is required.'));

    // Test validation of max_length machine name.
    $recipients = array('simpletest@example.com', 'simpletest2@example.com', 'simpletest3@example.com');
    $max_length = EntityTypeInterface::BUNDLE_MAX_LENGTH;
    $max_length_exceeded = $max_length + 1;
    $this->addContactForm($id = Unicode::strtolower($this->randomMachineName($max_length_exceeded)), $label = $this->randomMachineName($max_length_exceeded), implode(',', array($recipients[0])), '', TRUE);
    $this->assertText(format_string('Machine-readable name cannot be longer than !max characters but is currently !exceeded characters long.', array('!max' => $max_length, '!exceeded' => $max_length_exceeded)));
    $this->addContactForm($id = Unicode::strtolower($this->randomMachineName($max_length)), $label = $this->randomMachineName($max_length), implode(',', array($recipients[0])), '', TRUE);
    $this->assertRaw(t('Contact form %label has been added.', array('%label' => $label)));

    // Create first valid form.
    $this->addContactForm($id = Unicode::strtolower($this->randomMachineName(16)), $label = $this->randomMachineName(16), implode(',', array($recipients[0])), '', TRUE);
    $this->assertRaw(t('Contact form %label has been added.', array('%label' => $label)));

    // Check that the form was created in site default language.
    $langcode = $this->config('contact.form.' . $id)->get('langcode');
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $this->assertEqual($langcode, $default_langcode);

    // Make sure the newly created form is included in the list of forms.
    $this->assertNoUniqueText($label, 'New form included in forms list.');

    // Test update contact form.
    $this->updateContactForm($id, $label = $this->randomMachineName(16), $recipients_str = implode(',', array($recipients[0], $recipients[1])), $reply = $this->randomMachineName(30), FALSE);
    $config = $this->config('contact.form.' . $id)->get();
    $this->assertEqual($config['label'], $label);
    $this->assertEqual($config['recipients'], array($recipients[0], $recipients[1]));
    $this->assertEqual($config['reply'], $reply);
    $this->assertNotEqual($id, $this->config('contact.settings')->get('default_form'));
    $this->assertRaw(t('Contact form %label has been updated.', array('%label' => $label)));
    // Ensure the label is displayed on the contact page for this form.
    $this->drupalGet('contact/' . $id);
    $this->assertText($label);

    // Reset the form back to be the default form.
    $this->config('contact.settings')->set('default_form', $id)->save();

    // Ensure that the contact form is shown without a form selection input.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalLogout();
    $this->drupalGet('contact');
    $this->assertText(t('Your email address'));
    $this->assertNoText(t('Form'));
    $this->drupalLogin($admin_user);

    // Add more forms.
    $this->addContactForm(Unicode::strtolower($this->randomMachineName(16)), $label = $this->randomMachineName(16), implode(',', array($recipients[0], $recipients[1])), '', FALSE);
    $this->assertRaw(t('Contact form %label has been added.', array('%label' => $label)));

    $this->addContactForm($name = Unicode::strtolower($this->randomMachineName(16)), $label = $this->randomMachineName(16), implode(',', array($recipients[0], $recipients[1], $recipients[2])), '', FALSE);
    $this->assertRaw(t('Contact form %label has been added.', array('%label' => $label)));

    // Try adding a form that already exists.
    $this->addContactForm($name, $label, '', '', FALSE);
    $this->assertNoRaw(t('Contact form %label has been added.', array('%label' => $label)));
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
    $this->submitContact('', $recipients[0], $this->randomMachineName(16), $id, $this->randomMachineName(64));
    $this->assertText(t('Your name field is required.'));

    $this->submitContact($this->randomMachineName(16), '', $this->randomMachineName(16), $id, $this->randomMachineName(64));
    $this->assertText(t('Your email address field is required.'));

    $this->submitContact($this->randomMachineName(16), $invalid_recipients[0], $this->randomMachineName(16), $id, $this->randomMachineName(64));
    $this->assertRaw(t('The email address %mail is not valid.', array('%mail' => 'invalid')));

    $this->submitContact($this->randomMachineName(16), $recipients[0], '', $id, $this->randomMachineName(64));
    $this->assertText(t('Subject field is required.'));

    $this->submitContact($this->randomMachineName(16), $recipients[0], $this->randomMachineName(16), $id, '');
    $this->assertText(t('Message field is required.'));

    // Test contact form with no default form selected.
    $this->config('contact.settings')
      ->set('default_form', '')
      ->save();
    $this->drupalGet('contact');
    $this->assertResponse(404);

    // Try to access contact form with non-existing form IDs.
    $this->drupalGet('contact/0');
    $this->assertResponse(404);
    $this->drupalGet('contact/' . $this->randomMachineName());
    $this->assertResponse(404);

    // Submit contact form with correct values and check flood interval.
    for ($i = 0; $i < $flood_limit; $i++) {
      $this->submitContact($this->randomMachineName(16), $recipients[0], $this->randomMachineName(16), $id, $this->randomMachineName(64));
      $this->assertText(t('Your message has been sent.'));
    }
    // Submit contact form one over limit.
    $this->drupalGet('contact');
    $this->assertResponse(403);
    $this->assertRaw(t('You cannot send more than %number messages in @interval. Try again later.', array('%number' => $this->config('contact.settings')->get('flood.limit'), '@interval' => \Drupal::service('date.formatter')->formatInterval(600))));

    // Test listing controller.
    $this->drupalLogin($admin_user);

    $this->deleteContactForms();

    $label = $this->randomMachineName(16);
    $recipients = implode(',', array($recipients[0], $recipients[1], $recipients[2]));
    $contact_form = Unicode::strtolower($this->randomMachineName(16));
    $this->addContactForm($contact_form, $label, $recipients, '', FALSE);
    $this->drupalGet('admin/structure/contact');
    $this->clickLink(t('Edit'));
    $this->assertResponse(200);
    $this->assertFieldByName('label', $label);

    // Test field UI and field integration.
    $this->drupalGet('admin/structure/contact');

    // Find out in which row the form we want to add a field to is.
    $i = 0;
    foreach($this->xpath('//table/tbody/tr') as $row) {
      if (((string)$row->td[0]) == $label) {
        break;
      }
      $i++;
    }

    $this->clickLink(t('Manage fields'), $i);
    $this->assertResponse(200);
    $this->clickLink(t('Add field'));
    $this->assertResponse(200);

    // Create a simple textfield.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_label = $this->randomMachineName();
    $this->fieldUIAddNewField(NULL, $field_name, $field_label, 'text');
    $field_name = 'field_' . $field_name;

    // Check that the field is displayed.
    $this->drupalGet('contact/' . $contact_form);
    $this->assertText($field_label);

    // Submit the contact form and verify the content.
    $edit = array(
      'subject[0][value]' => $this->randomMachineName(),
      'message[0][value]' => $this->randomMachineName(),
      $field_name . '[0][value]' => $this->randomMachineName(),
    );
    $this->drupalPostForm(NULL, $edit, t('Send message'));
    $mails = $this->drupalGetMails();
    $mail = array_pop($mails);
    $this->assertEqual($mail['subject'], t('[@label] @subject', array('@label' => $label, '@subject' => $edit['subject[0][value]'])));
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

    // Set up three forms, 2 with an auto-reply and one without.
    $foo_autoreply = $this->randomMachineName(40);
    $bar_autoreply = $this->randomMachineName(40);
    $this->addContactForm('foo', 'foo', 'foo@example.com', $foo_autoreply, FALSE);
    $this->addContactForm('bar', 'bar', 'bar@example.com', $bar_autoreply, FALSE);
    $this->addContactForm('no_autoreply', 'no_autoreply', 'bar@example.com', '', FALSE);

    // Log the current user out in order to test the name and email fields.
    $this->drupalLogout();
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));

    // Test the auto-reply for form 'foo'.
    $email = $this->randomMachineName(32) . '@example.com';
    $subject = $this->randomMachineName(64);
    $this->submitContact($this->randomMachineName(16), $email, $subject, 'foo', $this->randomString(128));

    // We are testing the auto-reply, so there should be one email going to the sender.
    $captured_emails = $this->drupalGetMails(array('id' => 'contact_page_autoreply', 'to' => $email));
    $this->assertEqual(count($captured_emails), 1);
    $this->assertEqual(trim($captured_emails[0]['body']), trim(MailFormatHelper::htmlToText($foo_autoreply)));

    // Test the auto-reply for form 'bar'.
    $email = $this->randomMachineName(32) . '@example.com';
    $this->submitContact($this->randomMachineName(16), $email, $this->randomString(64), 'bar', $this->randomString(128));

    // Auto-reply for form 'bar' should result in one auto-reply email to the sender.
    $captured_emails = $this->drupalGetMails(array('id' => 'contact_page_autoreply', 'to' => $email));
    $this->assertEqual(count($captured_emails), 1);
    $this->assertEqual(trim($captured_emails[0]['body']), trim(MailFormatHelper::htmlToText($bar_autoreply)));

    // Verify that no auto-reply is sent when the auto-reply field is left blank.
    $email = $this->randomMachineName(32) . '@example.com';
    $this->submitContact($this->randomMachineName(16), $email, $this->randomString(64), 'no_autoreply', $this->randomString(128));
    $captured_emails = $this->drupalGetMails(array('id' => 'contact_page_autoreply', 'to' => $email));
    $this->assertEqual(count($captured_emails), 0);
  }

  /**
   * Adds a form.
   *
   * @param string $id
   *   The form machine name.
   * @param string $label
   *   The form label.
   * @param string $recipients
   *   The list of recipient email addresses.
   * @param string $reply
   *   The auto-reply text that is sent to a user upon completing the contact
   *   form.
   * @param boolean $selected
   *   A Boolean indicating whether the form should be selected by default.
   * @param array $third_party_settings
   *   Array of third party settings to be added to the posted form data.
   */
  function addContactForm($id, $label, $recipients, $reply, $selected, $third_party_settings = []) {
    $edit = array();
    $edit['label'] = $label;
    $edit['id'] = $id;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $edit += $third_party_settings;
    $this->drupalPostForm('admin/structure/contact/add', $edit, t('Save'));
  }

  /**
   * Updates a form.
   *
   * @param string $id
   *   The form machine name.
   * @param string $label
   *   The form label.
   * @param string $recipients
   *   The list of recipient email addresses.
   * @param string $reply
   *   The auto-reply text that is sent to a user upon completing the contact
   *   form.
   * @param boolean $selected
   *   A Boolean indicating whether the form should be selected by default.
   */
  function updateContactForm($id, $label, $recipients, $reply, $selected) {
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
   *   The email address of the sender.
   * @param string $subject
   *   The subject of the message.
   * @param string $id
   *   The form ID of the message.
   * @param string $message
   *   The message body.
   */
  function submitContact($name, $mail, $subject, $id, $message) {
    $edit = array();
    $edit['name'] = $name;
    $edit['mail'] = $mail;
    $edit['subject[0][value]'] = $subject;
    $edit['message[0][value]'] = $message;
    if ($id == $this->config('contact.settings')->get('default_form')) {
      $this->drupalPostForm('contact', $edit, t('Send message'));
    }
    else {
      $this->drupalPostForm('contact/' . $id, $edit, t('Send message'));
    }
  }

  /**
   * Deletes all forms.
   */
  function deleteContactForms() {
    $contact_forms = ContactForm::loadMultiple();;
    foreach ($contact_forms as $id => $contact_form) {
      if ($id == 'personal') {
        // Personal form could not be deleted.
        $this->drupalGet("admin/structure/contact/manage/$id/delete");
        $this->assertResponse(403);
      }
      else {
        $this->drupalPostForm("admin/structure/contact/manage/$id/delete", array(), t('Delete'));
        $this->assertRaw(t('Contact form %label has been deleted.', array('%label' => $contact_form->label())));
        $this->assertFalse(ContactForm::load($id), format_string('Form %contact_form not found', array('%contact_form' => $contact_form->label())));
      }
    }
  }

}
