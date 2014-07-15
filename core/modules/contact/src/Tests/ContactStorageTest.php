<?php

/**
 * @file
 * Contains \Drupal\contact\Tests\ContactStorageTest.
 */

namespace Drupal\contact\Tests;

use Drupal\contact\Entity\Message;

/**
 * Tests storing contact messages.
 */
class ContactStorageTest extends ContactSitewideTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'text',
    'contact',
    'field_ui',
    'contact_storage_test',
  );

  public static function getInfo() {
    return array(
      'name' => 'Contact Storage',
      'description' => 'Tests that contact messages can be stored.',
      'group' => 'Contact',
    );
  }

  /**
   * Tests configuration options and the site-wide contact form.
   */
  public function testContactStorage() {
    // Create and login administrative user.
    $admin_user = $this->drupalCreateUser(array(
      'access site-wide contact form',
      'administer contact forms',
      'administer users',
      'administer account settings',
      'administer contact_message fields',
    ));
    $this->drupalLogin($admin_user);
    // Create first valid category.
    $mail = 'simpletest@example.com';
    $this->addCategory($id = drupal_strtolower($this->randomName(16)), $label = $this->randomName(16), implode(',', array($mail)), '', TRUE);
    $this->assertRaw(t('Category %label has been added.', array('%label' => $label)));

    // Ensure that anonymous can submit site-wide contact form.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalLogout();
    $this->drupalGet('contact');
    $this->assertText(t('Your email address'));
    $this->assertNoText(t('Category'));
    $this->submitContact($name = $this->randomName(16), $mail, $subject = $this->randomName(16), $id, $message = $this->randomName(64));
    $this->assertText(t('Your message has been sent.'));

    $messages = Message::loadMultiple();
    /** @var \Drupal\contact\Entity\Message $message */
    $message = reset($messages);
    $this->assertEqual($message->getCategory()->id(), $id);
    $this->assertEqual($message->getSenderName(), $name);
    $this->assertEqual($message->getSubject(), $subject);
    $this->assertEqual($message->getSenderMail(), $mail);
  }

}
