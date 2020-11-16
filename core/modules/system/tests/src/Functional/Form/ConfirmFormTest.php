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
    $this->assertSession()->titleEquals("ConfirmFormTestForm::getQuestion(). | $site_name");
    $this->assertText('ConfirmFormTestForm::getDescription().', 'The description was used.');
    $this->assertSession()->buttonExists('ConfirmFormTestForm::getConfirmText().');

    // Test cancelling the form.
    $this->clickLink(t('ConfirmFormTestForm::getCancelText().'));
    $this->assertSession()->addressEquals('form-test/autocomplete');

    // Test submitting the form.
    $this->drupalPostForm('form-test/confirm-form', [], 'ConfirmFormTestForm::getConfirmText().');
    $this->assertText('The ConfirmFormTestForm::submitForm() method was used for this form.');
    $this->assertSession()->addressEquals('');

    // Test submitting the form with a destination.
    $this->drupalPostForm('form-test/confirm-form', [], 'ConfirmFormTestForm::getConfirmText().', ['query' => ['destination' => 'admin/config']]);
    $this->assertSession()->addressEquals('admin/config');

    // Test cancelling the form with a complex destination.
    $this->drupalGet('form-test/confirm-form-array-path');
    $this->clickLink(t('ConfirmFormArrayPathTestForm::getCancelText().'));
    // Verify that the form's complex cancel link was followed.
    $this->assertSession()->addressEquals('form-test/confirm-form?destination=admin/config');
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
