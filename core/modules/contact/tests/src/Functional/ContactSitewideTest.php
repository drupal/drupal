<?php

namespace Drupal\Tests\contact\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\user\RoleInterface;

/**
 * Tests site-wide contact form functionality.
 *
 * @see \Drupal\Tests\contact\Functional\ContactStorageTest
 *
 * @group contact
 */
class ContactSitewideTest extends BrowserTestBase {

  use FieldUiTestTrait;
  use AssertMailTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'text',
    'contact',
    'field_ui',
    'contact_test',
    'block',
    'error_service_test',
    'dblog',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests configuration options and the site-wide contact form.
   */
  public function testSiteWideContact() {
    // Tests name and email fields for authenticated and anonymous users.
    $this->drupalLogin($this->drupalCreateUser(['access site-wide contact form']));
    $this->drupalGet('contact');

    // Ensure that there is no textfield for name.
    $this->assertEmpty($this->xpath('//input[@name=:name]', [':name' => 'name']));

    // Ensure that there is no textfield for email.
    $this->assertEmpty($this->xpath('//input[@name=:name]', [':name' => 'mail']));

    // Logout and retrieve the page as an anonymous user
    $this->drupalLogout();
    user_role_grant_permissions('anonymous', ['access site-wide contact form']);
    $this->drupalGet('contact');

    // Ensure that there is textfield for name.
    $this->assertNotEmpty($this->xpath('//input[@name=:name]', [':name' => 'name']));

    // Ensure that there is textfield for email.
    $this->assertNotEmpty($this->xpath('//input[@name=:name]', [':name' => 'mail']));

    // Create and log in administrative user.
    $admin_user = $this->drupalCreateUser([
      'access site-wide contact form',
      'administer contact forms',
      'administer users',
      'administer account settings',
      'administer contact_message display',
      'administer contact_message fields',
      'administer contact_message form display',
    ]);
    $this->drupalLogin($admin_user);

    // Check the presence of expected cache tags.
    $this->drupalGet('contact');
    $this->assertCacheTag('config:contact.settings');

    $flood_limit = 3;
    $this->config('contact.settings')
      ->set('flood.limit', $flood_limit)
      ->set('flood.interval', 600)
      ->save();

    // Set settings.
    $edit = [];
    $edit['contact_default_status'] = TRUE;
    $this->drupalPostForm('admin/config/people/accounts', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    $this->drupalGet('admin/structure/contact');
    // Default form exists.
    $this->assertLinkByHref('admin/structure/contact/manage/feedback/delete');
    // User form could not be changed or deleted.
    // Cannot use ::assertNoLinkByHref as it does partial url matching and with
    // field_ui enabled admin/structure/contact/manage/personal/fields exists.
    // @todo: See https://www.drupal.org/node/2031223 for the above.
    $edit_link = $this->xpath('//a[@href=:href]', [
      ':href' => Url::fromRoute('entity.contact_form.edit_form', ['contact_form' => 'personal'])->toString(),
    ]);
    $this->assertTrue(empty($edit_link), new FormattableMarkup('No link containing href %href found.',
      ['%href' => 'admin/structure/contact/manage/personal']
    ));
    $this->assertNoLinkByHref('admin/structure/contact/manage/personal/delete');

    $this->drupalGet('admin/structure/contact/manage/personal');
    $this->assertSession()->statusCodeEquals(403);

    // Delete old forms to ensure that new forms are used.
    $this->deleteContactForms();
    $this->drupalGet('admin/structure/contact');
    $this->assertText('Personal', 'Personal form was not deleted');
    $this->assertNoLinkByHref('admin/structure/contact/manage/feedback');

    // Ensure that the contact form won't be shown without forms.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access site-wide contact form']);
    $this->drupalLogout();
    $this->drupalGet('contact');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalLogin($admin_user);
    $this->drupalGet('contact');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertText(t('The contact form has not been configured.'));
    // Test access personal form via site-wide contact page.
    $this->drupalGet('contact/personal');
    $this->assertSession()->statusCodeEquals(403);

    // Add forms.
    // Test invalid recipients.
    $invalid_recipients = ['invalid', 'invalid@', 'invalid@site.', '@site.', '@site.com'];
    foreach ($invalid_recipients as $invalid_recipient) {
      $this->addContactForm($this->randomMachineName(16), $this->randomMachineName(16), $invalid_recipient, '', FALSE);
      $this->assertRaw(t('%recipient is an invalid email address.', ['%recipient' => $invalid_recipient]));
    }

    // Test validation of empty form and recipients fields.
    $this->addContactForm('', '', '', '', TRUE);
    $this->assertText(t('Label field is required.'));
    $this->assertText(t('Machine-readable name field is required.'));
    $this->assertText(t('Recipients field is required.'));

    // Test validation of max_length machine name.
    $recipients = ['simpletest&@example.com', 'simpletest2@example.com', 'simpletest3@example.com'];
    $max_length = EntityTypeInterface::BUNDLE_MAX_LENGTH;
    $max_length_exceeded = $max_length + 1;
    $this->addContactForm($id = mb_strtolower($this->randomMachineName($max_length_exceeded)), $label = $this->randomMachineName($max_length_exceeded), implode(',', [$recipients[0]]), '', TRUE);
    $this->assertText(new FormattableMarkup('Machine-readable name cannot be longer than @max characters but is currently @exceeded characters long.', ['@max' => $max_length, '@exceeded' => $max_length_exceeded]));
    $this->addContactForm($id = mb_strtolower($this->randomMachineName($max_length)), $label = $this->randomMachineName($max_length), implode(',', [$recipients[0]]), '', TRUE);
    $this->assertText(t('Contact form @label has been added.', ['@label' => $label]));

    // Verify that the creation message contains a link to a contact form.
    $view_link = $this->xpath('//div[@class="messages"]//a[contains(@href, :href)]', [':href' => 'contact/']);
    $this->assert(isset($view_link), 'The message area contains a link to a contact form.');

    // Create first valid form.
    $this->addContactForm($id = mb_strtolower($this->randomMachineName(16)), $label = $this->randomMachineName(16), implode(',', [$recipients[0]]), '', TRUE);
    $this->assertText(t('Contact form @label has been added.', ['@label' => $label]));

    // Verify that the creation message contains a link to a contact form.
    $view_link = $this->xpath('//div[@class="messages"]//a[contains(@href, :href)]', [':href' => 'contact/']);
    $this->assert(isset($view_link), 'The message area contains a link to a contact form.');

    // Check that the form was created in site default language.
    $langcode = $this->config('contact.form.' . $id)->get('langcode');
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $this->assertEqual($langcode, $default_langcode);

    // Make sure the newly created form is included in the list of forms.
    $this->assertNoUniqueText($label, 'New form included in forms list.');

    // Ensure that the recipient email is escaped on the listing.
    $this->drupalGet('admin/structure/contact');
    $this->assertEscaped($recipients[0]);

    // Test update contact form.
    $this->updateContactForm($id, $label = $this->randomMachineName(16), $recipients_str = implode(',', [$recipients[0], $recipients[1]]), $reply = $this->randomMachineName(30), FALSE, 'Your message has been sent.', '/user');
    $config = $this->config('contact.form.' . $id)->get();
    $this->assertEqual($config['label'], $label);
    $this->assertEqual($config['recipients'], [$recipients[0], $recipients[1]]);
    $this->assertEqual($config['reply'], $reply);
    $this->assertNotEqual($id, $this->config('contact.settings')->get('default_form'));
    $this->assertText(t('Contact form @label has been updated.', ['@label' => $label]));
    // Ensure the label is displayed on the contact page for this form.
    $this->drupalGet('contact/' . $id);
    $this->assertText($label);

    // Reset the form back to be the default form.
    $this->config('contact.settings')->set('default_form', $id)->save();

    // Ensure that the contact form is shown without a form selection input.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access site-wide contact form']);
    $this->drupalLogout();
    $this->drupalGet('contact');
    $this->assertText(t('Your email address'));
    $this->assertNoText(t('Form'));
    $this->drupalLogin($admin_user);

    // Add more forms.
    $this->addContactForm(mb_strtolower($this->randomMachineName(16)), $label = $this->randomMachineName(16), implode(',', [$recipients[0], $recipients[1]]), '', FALSE);
    $this->assertText(t('Contact form @label has been added.', ['@label' => $label]));

    $this->addContactForm($name = mb_strtolower($this->randomMachineName(16)), $label = $this->randomMachineName(16), implode(',', [$recipients[0], $recipients[1], $recipients[2]]), '', FALSE);
    $this->assertText(t('Contact form @label has been added.', ['@label' => $label]));

    // Try adding a form that already exists.
    $this->addContactForm($name, $label, '', '', FALSE);
    $this->assertNoText(t('Contact form @label has been added.', ['@label' => $label]));
    $this->assertRaw(t('The machine-readable name is already in use. It must be unique.'));

    $this->drupalLogout();

    // Check to see that anonymous user cannot see contact page without permission.
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, ['access site-wide contact form']);
    $this->drupalGet('contact');
    $this->assertSession()->statusCodeEquals(403);

    // Give anonymous user permission and see that page is viewable.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access site-wide contact form']);
    $this->drupalGet('contact');
    $this->assertSession()->statusCodeEquals(200);

    // Submit contact form with invalid values.
    $this->submitContact('', $recipients[0], $this->randomMachineName(16), $id, $this->randomMachineName(64));
    $this->assertText(t('Your name field is required.'));

    $this->submitContact($this->randomMachineName(16), '', $this->randomMachineName(16), $id, $this->randomMachineName(64));
    $this->assertText(t('Your email address field is required.'));

    $this->submitContact($this->randomMachineName(16), $invalid_recipients[0], $this->randomMachineName(16), $id, $this->randomMachineName(64));
    $this->assertRaw(t('The email address %mail is not valid.', ['%mail' => 'invalid']));

    $this->submitContact($this->randomMachineName(16), $recipients[0], '', $id, $this->randomMachineName(64));
    $this->assertText(t('Subject field is required.'));

    $this->submitContact($this->randomMachineName(16), $recipients[0], $this->randomMachineName(16), $id, '');
    $this->assertText(t('Message field is required.'));

    // Test contact form with no default form selected.
    $this->config('contact.settings')
      ->set('default_form', '')
      ->save();
    $this->drupalGet('contact');
    $this->assertSession()->statusCodeEquals(404);

    // Try to access contact form with non-existing form IDs.
    $this->drupalGet('contact/0');
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet('contact/' . $this->randomMachineName());
    $this->assertSession()->statusCodeEquals(404);

    // Submit contact form with correct values and check flood interval.
    for ($i = 0; $i < $flood_limit; $i++) {
      $this->submitContact($this->randomMachineName(16), $recipients[0], $this->randomMachineName(16), $id, $this->randomMachineName(64));
      $this->assertText(t('Your message has been sent.'));
    }
    // Submit contact form one over limit.
    $this->submitContact($this->randomMachineName(16), $recipients[0], $this->randomMachineName(16), $id, $this->randomMachineName(64));
    $this->assertRaw(t('You cannot send more than %number messages in 10 min. Try again later.', ['%number' => $this->config('contact.settings')->get('flood.limit')]));

    // Test listing controller.
    $this->drupalLogin($admin_user);

    $this->deleteContactForms();

    $label = $this->randomMachineName(16);
    $recipients = implode(',', [$recipients[0], $recipients[1], $recipients[2]]);
    $contact_form = mb_strtolower($this->randomMachineName(16));
    $this->addContactForm($contact_form, $label, $recipients, '', FALSE);
    $this->drupalGet('admin/structure/contact');
    $this->clickLink(t('Edit'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFieldByName('label', $label);

    // Test field UI and field integration.
    $this->drupalGet('admin/structure/contact');

    $view_link = $this->xpath('//table/tbody/tr/td/a[contains(@href, :href) and text()=:text]', [
      ':href' => Url::fromRoute('entity.contact_form.canonical', ['contact_form' => $contact_form])->toString(),
      ':text' => $label,
      ]
    );
    $this->assertTrue(!empty($view_link), 'Contact listing links to contact form.');

    // Find out in which row the form we want to add a field to is.
    foreach ($this->xpath('//table/tbody/tr') as $row) {
      if ($row->findLink($label)) {
        $row->clickLink('Manage fields');
        break;
      }
    }

    $this->assertSession()->statusCodeEquals(200);
    $this->clickLink(t('Add field'));
    $this->assertSession()->statusCodeEquals(200);

    // Create a simple textfield.
    $field_name = mb_strtolower($this->randomMachineName());
    $field_label = $this->randomMachineName();
    $this->fieldUIAddNewField(NULL, $field_name, $field_label, 'text');
    $field_name = 'field_' . $field_name;

    // Check preview field can be ordered.
    $this->drupalGet('admin/structure/contact/manage/' . $contact_form . '/form-display');
    $this->assertText(t('Preview'));

    // Check that the field is displayed.
    $this->drupalGet('contact/' . $contact_form);
    $this->assertText($field_label);

    // Submit the contact form and verify the content.
    $edit = [
      'subject[0][value]' => $this->randomMachineName(),
      'message[0][value]' => $this->randomMachineName(),
      $field_name . '[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Send message'));
    $mails = $this->getMails();
    $mail = array_pop($mails);
    $this->assertEqual($mail['subject'], t('[@label] @subject', ['@label' => $label, '@subject' => $edit['subject[0][value]']]));
    $this->assertStringContainsString($field_label, $mail['body']);
    $this->assertStringContainsString($edit[$field_name . '[0][value]'], $mail['body']);

    // Test messages and redirect.
    /** @var \Drupal\contact\ContactFormInterface $form */
    $form = ContactForm::load($contact_form);
    $form->setMessage('Thanks for your submission.');
    $form->setRedirectPath('/user/' . $admin_user->id());
    $form->save();
    // Check that the field is displayed.
    $this->drupalGet('contact/' . $contact_form);

    // Submit the contact form and verify the content.
    $edit = [
      'subject[0][value]' => $this->randomMachineName(),
      'message[0][value]' => $this->randomMachineName(),
      $field_name . '[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Send message'));
    $this->assertText('Thanks for your submission.');
    $this->assertUrl('user/' . $admin_user->id());

    // Test Empty message.
    /** @var \Drupal\contact\ContactFormInterface $form */
    $form = ContactForm::load($contact_form);
    $form->setMessage('');
    $form->setRedirectPath('/user/' . $admin_user->id());
    $form->save();
    $this->drupalGet('admin/structure/contact/manage/' . $contact_form);
    // Check that the field is displayed.
    $this->drupalGet('contact/' . $contact_form);

    // Submit the contact form and verify the content.
    $edit = [
      'subject[0][value]' => $this->randomMachineName(),
      'message[0][value]' => $this->randomMachineName(),
      $field_name . '[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Send message'));
    $result = $this->xpath('//div[@role=:role]', [':role' => 'contentinfo']);
    $this->assertCount(0, $result, 'Messages not found.');
    $this->assertUrl('user/' . $admin_user->id());

    // Test preview and visibility of the message field and label. Submit the
    // contact form and verify the content.
    $edit = [
      'subject[0][value]' => $this->randomMachineName(),
      'message[0][value]' => $this->randomMachineName(),
      $field_name . '[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm($form->toUrl('canonical'), $edit, t('Preview'));

    // Message is now by default displayed twice, once for the form element and
    // once for the viewed message.
    $page_text = $this->getSession()->getPage()->getText();
    $this->assertGreaterThan(1, substr_count($page_text, t('Message')));
    $this->assertSession()->responseContains('class="field field--name-message field--type-string-long field--label-above');
    $this->assertSession()->pageTextContains($edit['message[0][value]']);

    // Hide the message field label.
    $display_edit = [
      'fields[message][label]' => 'hidden',
    ];
    $this->drupalPostForm('admin/structure/contact/manage/' . $contact_form . '/display', $display_edit, t('Save'));

    $this->drupalPostForm($form->toUrl('canonical'), $edit, t('Preview'));
    // Message should only be displayed once now.
    $page_text = $this->getSession()->getPage()->getText();
    $this->assertEquals(1, substr_count($page_text, t('Message')));
    $this->assertSession()->responseContains('class="field field--name-message field--type-string-long field--label-hidden field__item">');
    $this->assertSession()->pageTextContains($edit['message[0][value]']);
  }

  /**
   * Tests auto-reply on the site-wide contact form.
   */
  public function testAutoReply() {
    // Create and log in administrative user.
    $admin_user = $this->drupalCreateUser([
      'access site-wide contact form',
      'administer contact forms',
      'administer permissions',
      'administer users',
      'access site reports',
    ]);
    $this->drupalLogin($admin_user);

    // Set up three forms, 2 with an auto-reply and one without.
    $foo_autoreply = $this->randomMachineName(40);
    $bar_autoreply = $this->randomMachineName(40);
    $this->addContactForm('foo', 'foo', 'foo@example.com', $foo_autoreply, FALSE);
    $this->addContactForm('bar', 'bar', 'bar@example.com', $bar_autoreply, FALSE);
    $this->addContactForm('no_autoreply', 'no_autoreply', 'bar@example.com', '', FALSE);

    // Log the current user out in order to test the name and email fields.
    $this->drupalLogout();
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access site-wide contact form']);

    // Test the auto-reply for form 'foo'.
    $email = $this->randomMachineName(32) . '@example.com';
    $subject = $this->randomMachineName(64);
    $this->submitContact($this->randomMachineName(16), $email, $subject, 'foo', $this->randomString(128));

    // We are testing the auto-reply, so there should be one email going to the sender.
    $captured_emails = $this->getMails(['id' => 'contact_page_autoreply', 'to' => $email]);
    $this->assertCount(1, $captured_emails);
    $this->assertEqual(trim($captured_emails[0]['body']), trim(MailFormatHelper::htmlToText($foo_autoreply)));

    // Test the auto-reply for form 'bar'.
    $email = $this->randomMachineName(32) . '@example.com';
    $this->submitContact($this->randomMachineName(16), $email, $this->randomString(64), 'bar', $this->randomString(128));

    // Auto-reply for form 'bar' should result in one auto-reply email to the sender.
    $captured_emails = $this->getMails(['id' => 'contact_page_autoreply', 'to' => $email]);
    $this->assertCount(1, $captured_emails);
    $this->assertEqual(trim($captured_emails[0]['body']), trim(MailFormatHelper::htmlToText($bar_autoreply)));

    // Verify that no auto-reply is sent when the auto-reply field is left blank.
    $email = $this->randomMachineName(32) . '@example.com';
    $this->submitContact($this->randomMachineName(16), $email, $this->randomString(64), 'no_autoreply', $this->randomString(128));
    $captured_emails = $this->getMails(['id' => 'contact_page_autoreply', 'to' => $email]);
    $this->assertCount(0, $captured_emails);

    // Verify that the current error message doesn't show, that the auto-reply
    // doesn't get sent and the correct silent error gets logged.
    $email = '';
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('contact_message', 'foo')
      ->removeComponent('mail')
      ->save();
    $this->submitContact($this->randomMachineName(16), $email, $this->randomString(64), 'foo', $this->randomString(128));
    $this->assertNoText('Unable to send email. Contact the site administrator if the problem persists.');
    $captured_emails = $this->getMails(['id' => 'contact_page_autoreply', 'to' => $email]);
    $this->assertCount(0, $captured_emails);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/reports/dblog');
    $this->assertRaw('Error sending auto-reply, missing sender e-mail address in foo');
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
   * @param bool $selected
   *   A Boolean indicating whether the form should be selected by default.
   * @param string $message
   *   The message that will be displayed to a user upon completing the contact
   *   form.
   * @param array $third_party_settings
   *   Array of third party settings to be added to the posted form data.
   */
  public function addContactForm($id, $label, $recipients, $reply, $selected, $message = 'Your message has been sent.', $third_party_settings = []) {
    $edit = [];
    $edit['label'] = $label;
    $edit['id'] = $id;
    $edit['message'] = $message;
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
   * @param bool $selected
   *   A Boolean indicating whether the form should be selected by default.
   * @param string $message
   *   The message that will be displayed to a user upon completing the contact
   *   form.
   * @param string $redirect
   *   The path where user will be redirect after this form has been submitted..
   */
  public function updateContactForm($id, $label, $recipients, $reply, $selected, $message = 'Your message has been sent.', $redirect = '/') {
    $edit = [];
    $edit['label'] = $label;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $edit['message'] = $message;
    $edit['redirect'] = $redirect;
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
  public function submitContact($name, $mail, $subject, $id, $message) {
    $edit = [];
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
  public function deleteContactForms() {
    $contact_forms = ContactForm::loadMultiple();
    foreach ($contact_forms as $id => $contact_form) {
      if ($id == 'personal') {
        // Personal form could not be deleted.
        $this->drupalGet("admin/structure/contact/manage/$id/delete");
        $this->assertSession()->statusCodeEquals(403);
      }
      else {
        $this->drupalPostForm("admin/structure/contact/manage/$id/delete", [], t('Delete'));
        $this->assertRaw(t('The contact form %label has been deleted.', ['%label' => $contact_form->label()]));
        $this->assertNull(ContactForm::load($id), new FormattableMarkup('Form %contact_form not found', ['%contact_form' => $contact_form->label()]));
      }
    }
  }

}
