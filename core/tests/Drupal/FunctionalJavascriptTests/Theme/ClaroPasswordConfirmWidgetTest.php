<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Theme;

use Drupal\Tests\user\FunctionalJavascript\PasswordConfirmWidgetTest;

/**
 * Tests the password confirm widget with Claro theme.
 *
 * @group claro
 */
class ClaroPasswordConfirmWidgetTest extends PasswordConfirmWidgetTest {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * Tests that password match message is invisible when widget is initialized.
   */
  public function testPasswordConfirmMessage() {
    $this->drupalGet($this->testUser->toUrl('edit-form'));
    $password_confirm_widget_selector = '.js-form-type-password-confirm.js-form-item-pass';
    $password_confirm_selector = '.js-form-item-pass-pass2';
    $password_confirm_widget = $this->assert->elementExists('css', $password_confirm_widget_selector);
    $password_confirm_item = $password_confirm_widget->find('css', $password_confirm_selector);

    // Password match message.
    $this->assertTrue($password_confirm_item->has('css', 'input.js-password-confirm + [data-drupal-selector="password-confirm-message"]'));
    $this->assertFalse($password_confirm_item->find('css', 'input.js-password-confirm + [data-drupal-selector="password-confirm-message"]')->isVisible());
  }

  /**
   * {@inheritdoc}
   */
  public function testFillConfirmOnly() {
    // This test is not applicable to Claro because confirm field is hidden
    // until the password has been filled in the main field.
  }

}
