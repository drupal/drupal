<?php

/**
 * @file
 * Definition of Drupal\contact\ContactAuthenticatedUserTest.
 */

namespace Drupal\contact\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the contact form for authenticated users.
 */
class ContactAuthenticatedUserTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contact');

  public static function getInfo() {
    return array(
      'name' => 'Contact form textfields',
      'description' => 'Tests contact form textfields are present if authenticated.',
      'group' => 'Contact',
    );
  }

  /**
   * Tests that name and email fields are not present for authenticated users.
   */
  function testContactSiteWideTextfieldsLoggedInTestCase() {
    $this->drupalLogin($this->drupalCreateUser(array('access site-wide contact form')));
    $this->drupalGet('contact');

    // Ensure that there is no textfield for name.
    $this->assertFalse($this->xpath('//input[@name=:name]', array(':name' => 'name')));

    // Ensure that there is no textfield for email.
    $this->assertFalse($this->xpath('//input[@name=:name]', array(':name' => 'mail')));
  }
}
