<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Functional;

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
   * {@inheritdoc}
   */
  protected static $modules = [
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
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests configuration options and the site-wide contact form.
   */
  public function testSiteWideContact(): void {
    // Tests name and email fields for authenticated and anonymous users.
    $this->drupalLogin($this->drupalCreateUser([
      'access site-wide contact form',
    ]));
    $this->drupalGet('contact');

    // Ensure that there is no textfield for name.
    $this->assertSession()->fieldNotExists('name');

    // Ensure that there is no textfield for email.
    $this->assertSession()->fieldNotExists('mail');

    // Logout and retrieve the page as an anonymous user
    $this->drupalLogout();
    user_role_grant_permissions('anonymous', ['access site-wide contact form']);
    $this->drupalGet('contact');

    // Ensure that there is textfield for name.
    $this->assertSession()->fieldExists('name');

    // Ensure that there is textfield for email.
    $this->assertSession()->fieldExists('mail');

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
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:contact.settings');

    $flood_limit = 3;
    $this->config('contact.settings')
      ->set('flood.limit', $flood_limit)
      ->set('flood.interval', 600)
      ->save();

    // Set settings.
    $edit = [];
    $edit['contact_default_status'] = TRUE;
    $this->drupalGet('admin/config/people/accounts');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $this->drupalGet('admin/structure/contact');
    // Default form exists.
    $this->assertSession()->linkByHrefExists('admin/structure/contact/manage/feedback/delete');
    // User form could not be changed or deleted.
    // Cannot use ::assertNoLinkByHref as it does partial URL matching and with
    // field_ui enabled admin/structure/contact/manage/personal/fields exists.
    // @todo See https://www.drupal.org/node/2031223 for the above.
    $url = Url::fromRoute('entity.contact_form.edit_form', ['contact_form' => 'personal'])->toString();
    $this->assertSession()->elementNotExists('xpath', "//a[@href='{$url}']");
    $this->assertSession()->linkByHrefNotExists('admin/structure/contact/manage/personal/delete');

    $this->drupalGet('admin/structure/contact/manage/personal');
    $this->assertSession()->statusCodeEquals(403);

    // Delete old forms to ensure that new forms are used.
    $this->deleteContactForms();
    $this->drupalGet('admin/structure/contact');
    $this->assertSession()->pageTextContains('Personal');
    $this->assertSession()->linkByHrefNotExists('admin/structure/contact/manage/feedback');

    // Ensure that the contact form won't be shown without forms.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access site-wide contact form']);
    $this->drupalLogout();
    $this->drupalGet('contact');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalLogin($admin_user);
    $this->drupalGet('contact');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The contact form has not been configured.');
    // Test access personal form via site-wide contact page.
    $this->drupalGet('contact/personal');
    $this->assertSession()->statusCodeEquals(403);

    // Add forms.
    // Test invalid recipients.
    $invalid_recipients = ['invalid', 'invalid@', 'invalid@site.', '@site.', '@site.com'];
    foreach ($invalid_recipients as $invalid_recipient) {
      $this->addContactForm($this->randomMachineName(16), $this->randomMachineName(16), $invalid_recipient, '', FALSE);
      $this->assertSession()->pageTextContains($invalid_recipient . ' is an invalid email address.');
    }

    // Test validation of empty form and recipients fields.
    $this->addContactForm('', '', '', '', TRUE);
    $this->assertSession()->pageTextContains('Label field is required.');
    $this->assertSession()->pageTextContains('Machine-readable name field is required.');
    $this->assertSession()->pageTextContains('Recipients field is required.');

    // Test validation of max_length machine name.
    $recipients = ['simpletest&@example.com', 'simpletest2@example.com', 'simpletest3@example.com'];
    $max_length = EntityTypeInterface::BUNDLE_MAX_LENGTH;
    $max_length_exceeded = $max_length + 1;
    $this->addContactForm($id = $this->randomMachineName($max_length_exceeded), $label = $this->randomMachineName($max_length_exceeded), implode(',', [$recipients[0]]), '', TRUE);
    $this->assertSession()->pageTextContains('Machine-readable name cannot be longer than ' . $max_length . ' characters but is currently ' . $max_length_exceeded . ' characters long.');
    $this->addContactForm($id = $this->randomMachineName($max_length), $label = $this->randomMachineName($max_length), implode(',', [$recipients[0]]), '', TRUE);
    $this->assertSession()->pageTextContains('Contact form ' . $label . ' has been added.');

    // Verify that the creation message contains a link to a contact form.
    $this->assertSession()->elementExists('xpath', '//div[@data-drupal-messages]//a[contains(@href, "contact/")]');

    // Create first valid form.
    $this->addContactForm($id = $this->randomMachineName(16), $label = $this->randomMachineName(16), implode(',', [$recipients[0]]), '', TRUE);
    $this->assertSession()->pageTextContains('Contact form ' . $label . ' has been added.');

    // Verify that the creation message contains a link to a contact form.
    $this->assertSession()->elementExists('xpath', '//div[@data-drupal-messages]//a[contains(@href, "contact/")]');

    // Check that the form was created in site default language.
    $langcode = $this->config('contact.form.' . $id)->get('langcode');
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $this->assertEquals($default_langcode, $langcode);

    // Make sure the newly created form is included in the list of forms.
    $this->assertSession()->pageTextMatchesCount(2, '/' . $label . '/');

    // Ensure that the recipient email is escaped on the listing.
    $this->drupalGet('admin/structure/contact');
    $this->assertSession()->assertEscaped($recipients[0]);

    // Test update contact form.
    $this->updateContactForm($id, $label = $this->randomMachineName(16), implode(',', [$recipients[0], $recipients[1]]), $reply = $this->randomMachineName(30), FALSE, 'Your message has been sent.', '/user');
    $config = $this->config('contact.form.' . $id)->get();
    $this->assertEquals($label, $config['label']);
    $this->assertEquals([$recipients[0], $recipients[1]], $config['recipients']);
    $this->assertEquals($reply, $config['reply']);
    $this->assertNotEquals($this->config('contact.settings')->get('default_form'), $id);
    $this->assertSession()->pageTextContains('Contact form ' . $label . ' has been updated.');
    // Ensure the label is displayed on the contact page for this form.
    $this->drupalGet('contact/' . $id);
    $this->assertSession()->pageTextContains($label);

    // Reset the form back to be the default form.
    $this->config('contact.settings')->set('default_form', $id)->save();

    // Ensure that the contact form is shown without a form selection input.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access site-wide contact form']);
    $this->drupalLogout();
    $this->drupalGet('contact');
    $this->assertSession()->pageTextContains('Your email address');
    $this->assertSession()->pageTextNotContains('Form');
    $this->drupalLogin($admin_user);

    // Add more forms.
    $this->addContactForm($this->randomMachineName(16), $label = $this->randomMachineName(16), implode(',', [$recipients[0], $recipients[1]]), '', FALSE);
    $this->assertSession()->pageTextContains('Contact form ' . $label . ' has been added.');

    $this->addContactForm($name = $this->randomMachineName(16), $label = $this->randomMachineName(16), implode(',', [$recipients[0], $recipients[1], $recipients[2]]), '', FALSE);
    $this->assertSession()->pageTextContains('Contact form ' . $label . ' has been added.');

    // Try adding a form that already exists.
    $this->addContactForm($name, $label, '', '', FALSE);
    $this->assertSession()->pageTextNotContains("Contact form $label has been added.");
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');

    $this->drupalLogout();

    // Check to see that anonymous user cannot see contact page without
    // permission.
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, ['access site-wide contact form']);
    $this->drupalGet('contact');
    $this->assertSession()->statusCodeEquals(403);

    // Give anonymous user permission and see that page is viewable.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access site-wide contact form']);
    $this->drupalGet('contact');
    $this->assertSession()->statusCodeEquals(200);

    // Submit contact form with invalid values.
    $this->submitContact('', $recipients[0], $this->randomMachineName(16), $id, $this->randomMachineName(64));
    $this->assertSession()->pageTextContains('Your name field is required.');

    $this->submitContact($this->randomMachineName(16), '', $this->randomMachineName(16), $id, $this->randomMachineName(64));
    $this->assertSession()->pageTextContains('Your email address field is required.');

    $this->submitContact($this->randomMachineName(16), $invalid_recipients[0], $this->randomMachineName(16), $id, $this->randomMachineName(64));
    $this->assertSession()->pageTextContains('The email address invalid is not valid.');

    $this->submitContact($this->randomMachineName(16), $recipients[0], '', $id, $this->randomMachineName(64));
    $this->assertSession()->pageTextContains('Subject field is required.');

    $this->submitContact($this->randomMachineName(16), $recipients[0], $this->randomMachineName(16), $id, '');
    $this->assertSession()->pageTextContains('Message field is required.');

    // Test contact form with no default form selected.
    $this->config('contact.settings')
      ->set('default_form', NULL)
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
      $this->assertSession()->pageTextContains('Your message has been sent.');
    }
    // Submit contact form one over limit.
    $this->submitContact($this->randomMachineName(16), $recipients[0], $this->randomMachineName(16), $id, $this->randomMachineName(64));
    $this->assertSession()->pageTextContains('You cannot send more than ' . $this->config('contact.settings')->get('flood.limit') . ' messages in 10 min. Try again later.');

    // Test listing controller.
    $this->drupalLogin($admin_user);

    $this->deleteContactForms();

    $label = $this->randomMachineName(16);
    $recipients = implode(',', [$recipients[0], $recipients[1], $recipients[2]]);
    $contact_form = $this->randomMachineName(16);
    $this->addContactForm($contact_form, $label, $recipients, '', FALSE);
    $this->drupalGet('admin/structure/contact');
    $this->clickLink('Edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('label', $label);

    // Verify contact "View" tab exists.
    $this->assertSession()->linkExists('View');

    // Test field UI and field integration.
    $this->drupalGet('admin/structure/contact');

    // Test contact listing links to contact form.
    $this->assertSession()->elementExists('xpath', $this->assertSession()->buildXPathQuery('//table/tbody/tr/td/a[contains(@href, :href) and text()=:text]', [
      ':href' => Url::fromRoute('entity.contact_form.canonical', ['contact_form' => $contact_form])->toString(),
      ':text' => $label,
    ]));

    // Find out in which row the form we want to add a field to is.
    foreach ($this->xpath('//table/tbody/tr') as $row) {
      if ($row->findLink($label)) {
        $row->clickLink('Manage fields');
        break;
      }
    }

    $this->assertSession()->statusCodeEquals(200);
    $this->clickLink('Create a new field');
    $this->assertSession()->statusCodeEquals(200);

    // Create a simple textfield.
    $field_name = $this->randomMachineName();
    $field_label = $this->randomMachineName();
    $this->fieldUIAddNewField(NULL, $field_name, $field_label, 'text');
    $field_name = 'field_' . $field_name;

    // Check preview field can be ordered.
    $this->drupalGet('admin/structure/contact/manage/' . $contact_form . '/form-display');
    $this->assertSession()->pageTextContains('Preview');

    // Check that the field is displayed.
    $this->drupalGet('contact/' . $contact_form);
    $this->assertSession()->pageTextContains($field_label);

    // Submit the contact form and verify the content.
    $edit = [
      'subject[0][value]' => $this->randomMachineName(),
      'message[0][value]' => $this->randomMachineName(),
      $field_name . '[0][value]' => $this->randomMachineName(),
    ];
    $this->submitForm($edit, 'Send message');
    $mails = $this->getMails();
    $mail = array_pop($mails);
    $this->assertEquals(sprintf('[%s] %s', $label, $edit['subject[0][value]']), $mail['subject']);
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
    $this->submitForm($edit, 'Send message');
    $this->assertSession()->pageTextContains('Thanks for your submission.');
    $this->assertSession()->addressEquals('user/' . $admin_user->id());

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
    $this->submitForm($edit, 'Send message');
    // Verify that messages are not found.
    $this->assertSession()->elementNotExists('xpath', '//div[@data-drupal-messages]');
    $this->assertSession()->addressEquals('user/' . $admin_user->id());

    // Test preview and visibility of the message field and label. Submit the
    // contact form and verify the content.
    $edit = [
      'subject[0][value]' => $this->randomMachineName(),
      'message[0][value]' => $this->randomMachineName(),
      $field_name . '[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalGet($form->toUrl('canonical'));
    $this->submitForm($edit, 'Preview');

    // Message is now by default displayed twice, once for the form element and
    // once for the viewed message.
    $message = $edit['message[0][value]'];
    $this->assertSession()->pageTextMatchesCount(2, '/Message/');
    $this->assertSession()->pageTextMatchesCount(2, '/' . $message . '/');
    // Check for label and message in form element.
    $this->assertSession()->elementTextEquals('css', 'label[for="edit-message-0-value"]', 'Message');
    $this->assertSession()->fieldValueEquals('edit-message-0-value', $message);
    // Check for label and message in preview.
    $this->assertSession()->elementTextContains('css', '#edit-preview', 'Message');
    $this->assertSession()->elementTextContains('css', '#edit-preview', $message);

    // Hide the message field label.
    $display_edit = [
      'fields[message][label]' => 'hidden',
    ];
    $this->drupalGet('admin/structure/contact/manage/' . $contact_form . '/display');
    $this->submitForm($display_edit, 'Save');

    $this->drupalGet($form->toUrl('canonical'));
    $this->submitForm($edit, 'Preview');
    // 'Message' should only be displayed once now with the actual message
    // displayed twice.
    $this->assertSession()->pageTextContainsOnce('Message');
    $this->assertSession()->pageTextMatchesCount(2, '/' . $message . '/');
    // Check for label and message in form element.
    $this->assertSession()->elementTextEquals('css', 'label[for="edit-message-0-value"]', 'Message');
    $this->assertSession()->fieldValueEquals('edit-message-0-value', $message);
    // Check for message in preview but no label.
    $this->assertSession()->elementTextNotContains('css', '#edit-preview', 'Message');
    $this->assertSession()->elementTextContains('css', '#edit-preview', $message);

    // Set the preview field to 'hidden' in the view mode and check that the
    // field is hidden.
    $edit = [
      'fields[preview][region]' => 'hidden',
    ];
    $this->drupalGet('admin/structure/contact/manage/' . $contact_form . '/form-display');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->fieldExists('fields[preview][region]');

    // Check that the field preview is not displayed in the form.
    $this->drupalGet('contact/' . $contact_form);
    $this->assertSession()->responseNotContains('Preview');
  }

  /**
   * Tests auto-reply on the site-wide contact form.
   */
  public function testAutoReply(): void {
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

    // We are testing the auto-reply, so there should be one email going to the
    // sender.
    $captured_emails = $this->getMails(['id' => 'contact_page_autoreply', 'to' => $email]);
    $this->assertCount(1, $captured_emails);
    $this->assertEquals(trim(MailFormatHelper::htmlToText($foo_autoreply)), trim($captured_emails[0]['body']));

    // Test the auto-reply for form 'bar'.
    $email = $this->randomMachineName(32) . '@example.com';
    $this->submitContact($this->randomMachineName(16), $email, $this->randomString(64), 'bar', $this->randomString(128));

    // Auto-reply for form 'bar' should result in one auto-reply email to the
    // sender.
    $captured_emails = $this->getMails(['id' => 'contact_page_autoreply', 'to' => $email]);
    $this->assertCount(1, $captured_emails);
    $this->assertEquals(trim(MailFormatHelper::htmlToText($bar_autoreply)), trim($captured_emails[0]['body']));

    // Verify that no auto-reply is sent when the auto-reply field is left
    // blank.
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
    $this->assertSession()->pageTextNotContains('Unable to send email. Contact the site administrator if the problem persists.');
    $captured_emails = $this->getMails(['id' => 'contact_page_autoreply', 'to' => $email]);
    $this->assertCount(0, $captured_emails);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->responseContains('Error sending auto-reply, missing sender email address in foo');
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
  public function addContactForm($id, $label, $recipients, $reply, $selected, $message = 'Your message has been sent.', $third_party_settings = []): void {
    $edit = [];
    $edit['label'] = $label;
    $edit['id'] = $id;
    $edit['message'] = $message;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $edit += $third_party_settings;
    $this->drupalGet('admin/structure/contact/add');
    $this->submitForm($edit, 'Save');

    // Ensure the statically cached bundle info is aware of the contact form
    // that was just created in the UI.
    $this->container->get('entity_type.bundle.info')->clearCachedBundles();
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
  public function updateContactForm($id, $label, $recipients, $reply, $selected, $message = 'Your message has been sent.', $redirect = '/'): void {
    $edit = [];
    $edit['label'] = $label;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $edit['message'] = $message;
    $edit['redirect'] = $redirect;
    $this->drupalGet("admin/structure/contact/manage/{$id}");
    $this->submitForm($edit, 'Save');
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
  public function submitContact($name, $mail, $subject, $id, $message): void {
    $edit = [];
    $edit['name'] = $name;
    $edit['mail'] = $mail;
    $edit['subject[0][value]'] = $subject;
    $edit['message[0][value]'] = $message;
    if ($id == $this->config('contact.settings')->get('default_form')) {
      $this->drupalGet('contact');
      $this->submitForm($edit, 'Send message');
    }
    else {
      $this->drupalGet('contact/' . $id);
      $this->submitForm($edit, 'Send message');
    }
  }

  /**
   * Deletes all forms.
   */
  public function deleteContactForms(): void {
    $contact_forms = ContactForm::loadMultiple();
    foreach ($contact_forms as $id => $contact_form) {
      if ($id == 'personal') {
        // Personal form could not be deleted.
        $this->drupalGet("admin/structure/contact/manage/$id/delete");
        $this->assertSession()->statusCodeEquals(403);
      }
      else {
        $this->drupalGet("admin/structure/contact/manage/{$id}/delete");
        $this->submitForm([], 'Delete');
        $this->assertSession()->pageTextContains("The contact form {$contact_form->label()} has been deleted.");
        $this->assertNull(ContactForm::load($id), "Form {$contact_form->label()} not found");
      }
    }
  }

}
