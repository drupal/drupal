<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests confirmation forms.
 *
 * @group Form
 */
class ConfirmFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testConfirmForm() {
    // Test the building of the form.
    $this->drupalGet('form-test/confirm-form');
    $site_name = $this->config('system.site')->get('name');
    $this->assertSession()->titleEquals("ConfirmFormTestForm::getQuestion(). | $site_name");
    $this->assertSession()->pageTextContains('ConfirmFormTestForm::getDescription().');
    $this->assertSession()->buttonExists('ConfirmFormTestForm::getConfirmText().');

    // Test cancelling the form.
    $this->clickLink('ConfirmFormTestForm::getCancelText().');
    $this->assertSession()->addressEquals('form-test/autocomplete');

    // Test submitting the form.
    $this->drupalGet('form-test/confirm-form');
    $this->submitForm([], 'ConfirmFormTestForm::getConfirmText().');
    $this->assertSession()->pageTextContains('The ConfirmFormTestForm::submitForm() method was used for this form.');
    $this->assertSession()->addressEquals('');

    // Test submitting the form with a destination.
    $this->drupalGet('form-test/confirm-form', ['query' => ['destination' => 'admin/config']]);
    $this->submitForm([], 'ConfirmFormTestForm::getConfirmText().');
    $this->assertSession()->addressEquals('admin/config');

    // Test cancelling the form with a complex destination.
    $this->drupalGet('form-test/confirm-form-array-path');
    $this->clickLink('ConfirmFormArrayPathTestForm::getCancelText().');
    // Verify that the form's complex cancel link was followed.
    $this->assertSession()->addressEquals('form-test/confirm-form?destination=admin/config');
  }

  /**
   * Tests that the confirm form does not use external destinations.
   */
  public function testConfirmFormWithExternalDestination() {
    $this->drupalGet('form-test/confirm-form');
    $this->assertSession()->linkByHrefExists(Url::fromRoute('form_test.route8')->toString());
    $this->drupalGet('form-test/confirm-form', ['query' => ['destination' => 'node']]);
    $this->assertSession()->linkByHrefExists(Url::fromUri('internal:/node')->toString());
    $this->drupalGet('form-test/confirm-form', ['query' => ['destination' => 'http://example.com']]);
    $this->assertSession()->linkByHrefExists(Url::fromRoute('form_test.route8')->toString());
    $this->drupalGet('form-test/confirm-form', ['query' => ['destination' => '<front>']]);
    $this->assertSession()->linkByHrefExists(Url::fromRoute('<front>')->toString());
    // Other invalid destinations, should fall back to the form default.
    $this->drupalGet('form-test/confirm-form', ['query' => ['destination' => '/http://example.com']]);
    $this->assertSession()->linkByHrefExists(Url::fromRoute('form_test.route8')->toString());
  }

}
