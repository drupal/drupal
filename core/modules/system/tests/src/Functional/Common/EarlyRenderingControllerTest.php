<?php

namespace Drupal\Tests\system\Functional\Common;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Verifies that bubbleable metadata of early rendering is not lost.
 *
 * @group Common
 */
class EarlyRenderingControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'early_rendering_controller_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests theme preprocess functions being able to attach assets.
   */
  public function testEarlyRendering() {
    // Render array: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.render_array'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hello world!');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.render_array.early'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hello world!');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'foo');

    // AjaxResponse: non-early & early.
    // @todo Add cache tags assertion when AjaxResponse is made cacheable in
    //   https://www.drupal.org/node/956186.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.ajax_response'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hello world!');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.ajax_response.early'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hello world!');

    // Basic Response object: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.response'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hello world!');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.response.early'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hello world!');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'foo');

    // Response object with attachments: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.response-with-attachments'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hello world!');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.response-with-attachments.early'));
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSession()->pageTextContains('The controller result claims to be providing relevant cache metadata, but leaked metadata was detected. Ensure you are not rendering content too early. Returned object class: Drupal\early_rendering_controller_test\AttachmentsTestResponse.');

    // Cacheable Response object: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.cacheable-response'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hello world!');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.cacheable-response.early'));
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSession()->pageTextContains('The controller result claims to be providing relevant cache metadata, but leaked metadata was detected. Ensure you are not rendering content too early. Returned object class: Drupal\early_rendering_controller_test\CacheableTestResponse.');

    // Basic domain object: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.domain-object'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('TestDomainObject');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.domain-object.early'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('TestDomainObject');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'foo');

    // Basic domain object with attachments: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.domain-object-with-attachments'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('AttachmentsTestDomainObject');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.domain-object-with-attachments.early'));
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSession()->pageTextContains('The controller result claims to be providing relevant cache metadata, but leaked metadata was detected. Ensure you are not rendering content too early. Returned object class: Drupal\early_rendering_controller_test\AttachmentsTestDomainObject.');

    // Cacheable Response object: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.cacheable-domain-object'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('CacheableTestDomainObject');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.cacheable-domain-object.early'));
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSession()->pageTextContains('The controller result claims to be providing relevant cache metadata, but leaked metadata was detected. Ensure you are not rendering content too early. Returned object class: Drupal\early_rendering_controller_test\CacheableTestDomainObject.');

    // The exceptions are expected. Do not interpret them as a test failure.
    // Not using File API; a potential error must trigger a PHP warning.
    unlink($this->root . '/' . $this->siteDirectory . '/error.log');
  }

}
