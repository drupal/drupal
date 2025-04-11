<?php

declare(strict_types=1);

namespace Drupal\Tests\user\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests user registration forms via JS.
 *
 * @group user
 */
class UserRegisterFormTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests if registration form writes to localStorage.
   */
  public function testRegistrationFormStorage(): void {

    // Load register form.
    $this->drupalGet('user/register');

    // Register user.
    $name = $this->randomMachineName();

    $page = $this->getSession()->getPage();
    $page->fillField('edit-name', $name);
    $page->fillField('edit-mail', $name . '@example.com');
    $page->pressButton('edit-submit');

    // Test if localStorage is set now.
    $this->assertJsCondition("localStorage.getItem('Drupal.visitor.name') === null", 10000, 'Failed to assert that the visitor name was not written to localStorage.');

  }

}
