<?php

namespace Drupal\Tests\contact\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests contact form textfields are present if authenticated.
 *
 * @group contact
 */
class ContactAuthenticatedUserTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['contact'];

  /**
   * Tests that name and email fields are not present for authenticated users.
   */
  public function testContactSiteWideTextfieldsLoggedInTestCase() {
    $this->drupalLogin($this->drupalCreateUser(['access site-wide contact form']));
    $this->drupalGet('contact');

    // Ensure that there is no textfield for name.
    $this->assertFalse($this->xpath('//input[@name=:name]', [':name' => 'name']));

    // Ensure that there is no textfield for email.
    $this->assertFalse($this->xpath('//input[@name=:name]', [':name' => 'mail']));
  }

}
