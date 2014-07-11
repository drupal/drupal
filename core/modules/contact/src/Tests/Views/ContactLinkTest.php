<?php

/**
 * @file
 * Contains \Drupal\contact\Tests\Views\ContactLinkTest.
 */

namespace Drupal\contact\Tests\Views;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the contact link field.
 *
 * @group contact
 * @see \Drupal\contact\Plugin\views\field\ContactLink.
 */
class ContactLinkTest extends ViewTestBase {

  /**
   * Stores the user data service used by the test.
   *
   * @var \Drupal\user\UserDataInterface
   */
  public $userData;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contact_test_views');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_contact_link');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), array('contact_test_views'));

    $this->userData = $this->container->get('user.data');
  }

  /**
   * Tests contact link.
   */
  public function testContactLink() {
    $accounts = array();
    $accounts['root'] = user_load(1);
    // Create an account with access to all contact pages.
    $admin_account = $this->drupalCreateUser(array('administer users'));
    $accounts['admin'] = $admin_account;
    // Create an account with no access to contact pages.
    $no_contact_account = $this->drupalCreateUser();
    $accounts['no_contact'] = $no_contact_account;

    // Create an account with access to contact pages.
    $contact_account = $this->drupalCreateUser(array('access user contact forms'));
    $accounts['contact'] = $contact_account;

    $this->drupalLogin($admin_account);
    $this->drupalGet('test-contact-link');
    // The admin user has access to all contact links beside his own.
    $this->assertContactLinks($accounts, array('root', 'no_contact', 'contact'));

    $this->drupalLogin($no_contact_account);
    $this->drupalGet('test-contact-link');
    // Ensure that the user without the permission doesn't see any link.
    $this->assertContactLinks($accounts, array());

    $this->drupalLogin($contact_account);
    $this->drupalGet('test-contact-link');
    $this->assertContactLinks($accounts, array('root', 'admin', 'no_contact'));

    // Disable contact link for no_contact.
    $this->userData->set('contact', $no_contact_account->id(), 'enabled', FALSE);
    $this->drupalGet('test-contact-link');
    $this->assertContactLinks($accounts, array('root', 'admin'));
  }

  /**
   * Asserts whether certain users contact links appear on the page.
   *
   * @param array $accounts
   *   All user objects used by the test.
   * @param array $names
   *   Users which should have contact links.
   */
  public function assertContactLinks(array $accounts, array $names) {
    $result = $this->xpath('//div[contains(@class, "views-field-contact")]//a');
    $this->assertEqual(count($result), count($names));
    foreach ($names as $name) {
      $account = $accounts[$name];

      $result = $this->xpath('//div[contains(@class, "views-field-contact")]//a[contains(@href, :url)]', array(':url' => $account->url('contact-form')));
      $this->assertTrue(count($result));
    }
  }

}
