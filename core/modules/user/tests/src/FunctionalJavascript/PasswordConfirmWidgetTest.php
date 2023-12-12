<?php

declare(strict_types=1);

namespace Drupal\Tests\user\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JS components added to the PasswordConfirm render element.
 *
 * @group user
 */
class PasswordConfirmWidgetTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * WebAssert object.
   *
   * @var \Drupal\Tests\WebAssert
   */
  protected $assert;

  /**
   * User for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->assert = $this->assertSession();

    // Create a user.
    $this->testUser = $this->createUser();
    $this->drupalLogin($this->testUser);
  }

  /**
   * Tests the components added to the password confirm widget.
   */
  public function testPasswordConfirmWidgetJsComponents() {
    $this->drupalGet($this->testUser->toUrl('edit-form'));

    $password_confirm_widget_selector = '.js-form-type-password-confirm.js-form-item-pass';
    $password_parent_selector = '.js-form-item-pass-pass1';
    $password_confirm_selector = '.js-form-item-pass-pass2';
    $password_confirm_widget = $this->assert->elementExists('css', $password_confirm_widget_selector);
    $password_parent_item = $password_confirm_widget->find('css', $password_parent_selector);
    $password_confirm_item = $password_confirm_widget->find('css', $password_confirm_selector);

    // Check that 'password-parent' and 'confirm-parent' are added to the
    // appropriate elements.
    $this->assertNotNull($this->assert->waitForElement('css', "$password_parent_selector.password-parent"));
    $this->assertTrue($password_parent_item->hasClass('password-parent'));
    $this->assertNotNull($this->assert->waitForElement('css', "$password_confirm_selector.confirm-parent"));
    $this->assertTrue($password_confirm_item->hasClass('confirm-parent'));

    // Check the elements of the main password item.
    $this->assertTrue($password_parent_item->has('css', 'input.js-password-field'));

    // Strength meter and bar.
    $this->assertTrue($password_parent_item->has('css', 'input.js-password-field + .password-strength > [data-drupal-selector="password-strength-meter"]:first-child [data-drupal-selector="password-strength-indicator"]'));

    // Password strength feedback. No strength text feedback present without
    // input.
    $this->assertTrue($password_parent_item->has('css', '.password-strength > [data-drupal-selector="password-strength-meter"] + .password-strength__title:last-child > [data-drupal-selector="password-strength-text"]'));
    $this->assertEmpty($password_parent_item->find('css', '.password-strength > [data-drupal-selector="password-strength-meter"] + .password-strength__title:last-child > [data-drupal-selector="password-strength-text"]')->getText());

    // Check the elements of the password confirm item.
    $this->assertTrue($password_confirm_item->has('css', 'input.js-password-confirm'));

    // Check the password suggestions element.
    $this->assertTrue($password_confirm_item->has('css', "$password_confirm_selector + .password-suggestions"));
    $this->assertFalse($password_confirm_item->has('css', "$password_confirm_selector + .password-suggestions > ul > li"));
    $this->assertFalse($password_confirm_item->find('css', "$password_confirm_selector + .password-suggestions")->isVisible());
    $this->assertTrue($password_confirm_item->find('css', "$password_confirm_selector + .password-suggestions")->getHtml() === '');

    // Fill only the main input for first.
    $this->drupalGet($this->testUser->toUrl('edit-form'));

    // Wait for the JS.
    $this->assert->waitForElement('css', "$password_parent_selector.password-parent");

    // Fill main input.
    $password_confirm_widget->fillField('Password', 'o');

    // Password tips should be refreshed and get visible.
    $this->assertNotNull($this->assert->waitForElement('css', "$password_confirm_selector + .password-suggestions > ul > li"));
    $this->assertTrue($password_confirm_item->find('css', "$password_confirm_selector + .password-suggestions > ul > li")->isVisible());

    // Password match message must become invisible.
    $this->assertFalse($password_confirm_item->find('css', 'input.js-password-confirm + [data-drupal-selector="password-confirm-message"]')->isVisible());

    // Password strength message should be updated.
    $this->assert->elementContains('css', "$password_confirm_widget_selector $password_parent_selector", '<div aria-live="polite" aria-atomic="true" class="password-strength__title">Password strength: <span class="password-strength__text" data-drupal-selector="password-strength-text">Weak</span></div>');

    // Deleting the input must not change the element above.
    $password_confirm_widget->fillField('Password', 'o');
    $this->assertFalse($password_confirm_item->find('css', 'input.js-password-confirm + [data-drupal-selector="password-confirm-message"]')->isVisible());
    $this->assertTrue($password_confirm_item->find('css', "$password_confirm_selector + .password-suggestions > ul > li")->isVisible());
    $this->assert->elementContains('css', "$password_confirm_widget_selector $password_parent_selector", '<div aria-live="polite" aria-atomic="true" class="password-strength__title">Password strength: <span class="password-strength__text" data-drupal-selector="password-strength-text">Weak</span></div>');

    // Now fill both the main and confirm input.
    $password_confirm_widget->fillField('Password', 'oooooooooO0∘');
    $password_confirm_widget->fillField('Confirm password', 'oooooooooO0∘');

    // Bar should be 100% wide.
    $this->assert->elementAttributeContains('css', 'input.js-password-field + .password-strength > [data-drupal-selector="password-strength-meter"] [data-drupal-selector="password-strength-indicator"]', 'style', 'width: 100%;');
    $this->assert->elementTextContains('css', "$password_confirm_widget_selector $password_parent_selector [data-drupal-selector='password-strength-text']", 'Strong');

    // Password match message must be visible.
    $this->assertTrue($password_confirm_item->find('css', 'input.js-password-confirm + [data-drupal-selector="password-confirm-message"]')->isVisible());
    $this->assertTrue($password_confirm_item->find('css', 'input.js-password-confirm + [data-drupal-selector="password-confirm-message"] > [data-drupal-selector="password-match-status-text"]')->hasClass('ok'));
    $this->assert->elementTextContains('css', 'input.js-password-confirm + [data-drupal-selector="password-confirm-message"] > [data-drupal-selector="password-match-status-text"]', 'yes');

    // Password suggestions should get invisible.
    $this->assertFalse($password_confirm_item->find('css', "$password_confirm_selector + .password-suggestions")->isVisible());
  }

  /**
   * Ensures that password match message is visible when widget is initialized.
   */
  public function testPasswordConfirmMessage() {
    $this->drupalGet($this->testUser->toUrl('edit-form'));
    $password_confirm_widget_selector = '.js-form-type-password-confirm.js-form-item-pass';
    $password_confirm_selector = '.js-form-item-pass-pass2';
    $password_confirm_widget = $this->assert->elementExists('css', $password_confirm_widget_selector);
    $password_confirm_item = $password_confirm_widget->find('css', $password_confirm_selector);

    // Password match message.
    $this->assertTrue($password_confirm_item->has('css', 'input.js-password-confirm + [data-drupal-selector="password-confirm-message"]'));
    $this->assertTrue($password_confirm_item->find('css', 'input.js-password-confirm + [data-drupal-selector="password-confirm-message"]')->isVisible());
    $this->assert->elementContains('css', "$password_confirm_widget_selector $password_confirm_selector", '<div aria-live="polite" aria-atomic="true" class="password-confirm-message" data-drupal-selector="password-confirm-message">Passwords match: <span data-drupal-selector="password-match-status-text"></span></div>');
  }

  /**
   * Tests the password confirm widget so that only confirm input is filled.
   */
  public function testFillConfirmOnly() {
    $this->drupalGet($this->testUser->toUrl('edit-form'));
    $password_confirm_widget_selector = '.js-form-type-password-confirm.js-form-item-pass';
    $password_parent_selector = '.js-form-item-pass-pass1';
    $password_confirm_selector = '.js-form-item-pass-pass2';
    $password_confirm_widget = $this->assert->elementExists('css', $password_confirm_widget_selector);
    $password_confirm_item = $password_confirm_widget->find('css', $password_confirm_selector);
    $password_parent_item = $password_confirm_widget->find('css', $password_parent_selector);

    // Fill only the confirm input.
    $password_confirm_widget->fillField('Confirm password', 'o');

    // Password tips should be refreshed and get visible.
    $this->assertNotNull($this->assert->waitForElement('css', "$password_confirm_selector + .password-suggestions > ul > li"));
    $this->assertTrue($password_confirm_item->find('css', "$password_confirm_selector + .password-suggestions")->isVisible());

    // The appropriate strength text should appear.
    $this->assert->elementContains('css', "$password_confirm_widget_selector $password_parent_selector", '<div aria-live="polite" aria-atomic="true" class="password-strength__title">Password strength: <span class="password-strength__text" data-drupal-selector="password-strength-text">Weak</span></div>');

    // Password match.
    $this->assertTrue($password_confirm_item->find('css', 'input.js-password-confirm + [data-drupal-selector="password-confirm-message"]')->isVisible());
    $this->assert->elementContains('css', "$password_confirm_widget_selector $password_confirm_selector [data-drupal-selector='password-confirm-message']", 'Passwords match: <span data-drupal-selector="password-match-status-text" class="error">no</span>');

    // Deleting the input should hide the 'password match', but password
    // strength and tips must remain visible.
    $password_confirm_widget->fillField('Confirm password', '');
    $this->assertFalse($password_confirm_item->find('css', 'input.js-password-confirm + [data-drupal-selector="password-confirm-message"]')->isVisible());
    $this->assert->elementContains('css', "$password_confirm_widget_selector $password_confirm_selector [data-drupal-selector='password-confirm-message']", 'Passwords match: <span data-drupal-selector="password-match-status-text" class="error">no</span>');
    $this->assertTrue($password_confirm_item->find('css', "$password_confirm_selector + .password-suggestions")->isVisible());
    $this->assertTrue($password_parent_item->find('css', '.password-strength > .password-strength__meter + .password-strength__title')->isVisible());
    $this->assert->elementContains('css', "$password_confirm_widget_selector $password_parent_selector", '<div aria-live="polite" aria-atomic="true" class="password-strength__title">Password strength: <span class="password-strength__text" data-drupal-selector="password-strength-text">Weak</span></div>');
  }

}
