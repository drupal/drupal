<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Component\Render\FormattableMarkup;
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
    $this->assertTitle("ConfirmFormTestForm::getQuestion(). | $site_name");
    $this->assertText(t('ConfirmFormTestForm::getDescription().'), 'The description was used.');
    $this->assertFieldByXPath('//input[@id="edit-submit"]', t('ConfirmFormTestForm::getConfirmText().'), 'The confirm text was used.');

    // Test cancelling the form.
    $this->clickLink(t('ConfirmFormTestForm::getCancelText().'));
    $this->assertUrl('form-test/autocomplete', [], "The form's cancel link was followed.");

    // Test submitting the form.
    $this->drupalPostForm('form-test/confirm-form', NULL, t('ConfirmFormTestForm::getConfirmText().'));
    $this->assertText('The ConfirmFormTestForm::submitForm() method was used for this form.');
    $this->assertUrl('', [], "The form's redirect was followed.");

    // Test submitting the form with a destination.
    $this->drupalPostForm('form-test/confirm-form', NULL, t('ConfirmFormTestForm::getConfirmText().'), ['query' => ['destination' => 'admin/config']]);
    $this->assertUrl('admin/config', [], "The form's redirect was not followed, the destination query string was followed.");

    // Test cancelling the form with a complex destination.
    $this->drupalGet('form-test/confirm-form-array-path');
    $this->clickLink(t('ConfirmFormArrayPathTestForm::getCancelText().'));
    $this->assertUrl('form-test/confirm-form', ['query' => ['destination' => 'admin/config']], "The form's complex cancel link was followed.");
  }

  /**
   * Tests that the confirm form does not use external destinations.
   */
  public function testConfirmFormWithExternalDestination() {
    $this->drupalGet('form-test/confirm-form');
    $this->assertCancelLinkUrl(Url::fromRoute('form_test.route8'));
    $this->drupalGet('form-test/confirm-form', ['query' => ['destination' => 'node']]);
    $this->assertCancelLinkUrl(Url::fromUri('internal:/node'));
    $this->drupalGet('form-test/confirm-form', ['query' => ['destination' => 'http://example.com']]);
    $this->assertCancelLinkUrl(Url::fromRoute('form_test.route8'));
    $this->drupalGet('form-test/confirm-form', ['query' => ['destination' => '<front>']]);
    $this->assertCancelLinkUrl(Url::fromRoute('<front>'));
    // Other invalid destinations, should fall back to the form default.
    $this->drupalGet('form-test/confirm-form', ['query' => ['destination' => '/http://example.com']]);
    $this->assertCancelLinkUrl(Url::fromRoute('form_test.route8'));
  }

  /**
   * Asserts that a cancel link is present pointing to the provided URL.
   *
   * @param \Drupal\Core\Url $url
   *   The url to check for.
   * @param string $message
   *   The assert message.
   * @param string $group
   *   The assertion group.
   *
   * @return bool
   *   Result of the assertion.
   */
  public function assertCancelLinkUrl(Url $url, $message = '', $group = 'Other') {
    $links = $this->xpath('//a[@href=:url]', [':url' => $url->toString()]);
    $message = ($message ? $message : new FormattableMarkup('Cancel link with URL %url found.', ['%url' => $url->toString()]));
    return $this->assertTrue(isset($links[0]), $message, $group);
  }

}
