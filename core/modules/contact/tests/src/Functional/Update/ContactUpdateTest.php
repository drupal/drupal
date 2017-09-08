<?php

namespace Drupal\Tests\contact\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests contact update path.
 *
 * @group contact
 */
class ContactUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests contact_form updates.
   *
   * @see contact_post_update_add_message_redirect_field_to_contact_form()
   */
  public function testPostUpdateContactFormFields() {
    // Check that contact_form does not have fields redirect and message.
    $config_factory = \Drupal::configFactory();
    // Check that contact_form entities are more than zero.
    $contact_forms = $config_factory->listAll('contact.form.');
    $this->assertTrue(count($contact_forms), 'There are contact forms to update.');
    foreach ($contact_forms as $contact_config_name) {
      $contact_form_data = $config_factory->get($contact_config_name)->get();
      $this->assertFalse(isset($contact_form_data['message']), 'Prior to running the update the "message" key does not exist.');
      $this->assertFalse(isset($contact_form_data['redirect']), 'Prior to running the update the "redirect" key does not exist.');
    }

    // Run updates.
    $this->runUpdates();

    // Check that the contact_form entities have been updated.
    foreach ($contact_forms as $contact_config_name) {
      $contact_form_data = $config_factory->get($contact_config_name)->get();
      $this->assertTrue(isset($contact_form_data['message']), 'After running the update the "message" key exists.');
      $this->assertEqual('Your message has been sent.', $contact_form_data['message']);
      $this->assertTrue(isset($contact_form_data['redirect']), 'After running the update the "redirect" key exists.');
      $this->assertEqual('', $contact_form_data['redirect']);
    }
  }

}
