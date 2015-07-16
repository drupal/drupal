<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Common\EarlyRenderingControllerTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Verifies that bubbleable metadata of early rendering is not lost.
 *
 * @group Common
 */
class EarlyRenderingControllerTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dumpHeaders = TRUE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'early_rendering_controller_test'];

  /**
   * Tests theme preprocess functions being able to attach assets.
   */
  function testEarlyRendering() {
    // Render array: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.render_array'));
    $this->assertResponse(200);
    $this->assertRaw('Hello world!');
    $this->assertCacheTag('foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.render_array.early'));
    $this->assertResponse(200);
    $this->assertRaw('Hello world!');
    $this->assertCacheTag('foo');

    // AjaxResponse: non-early & early.
    // @todo Add cache tags assertion when AjaxResponse is made cacheable in
    //   https://www.drupal.org/node/956186.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.ajax_response'));
    $this->assertResponse(200);
    $this->assertRaw('Hello world!');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.ajax_response.early'));
    $this->assertResponse(200);
    $this->assertRaw('Hello world!');

    // Basic Response object: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.response'));
    $this->assertResponse(200);
    $this->assertRaw('Hello world!');
    $this->assertNoCacheTag('foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.response.early'));
    $this->assertResponse(200);
    $this->assertRaw('Hello world!');
    $this->assertNoCacheTag('foo');

    // Response object with attachments: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.response-with-attachments'));
    $this->assertResponse(200);
    $this->assertRaw('Hello world!');
    $this->assertNoCacheTag('foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.response-with-attachments.early'));
    $this->assertResponse(500);
    $this->assertRaw('The controller result claims to be providing relevant cache metadata, but leaked metadata was detected. Please ensure you are not rendering content too early. Returned object class: Drupal\early_rendering_controller_test\AttachmentsTestResponse.');

    // Cacheable Response object: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.cacheable-response'));
    $this->assertResponse(200);
    $this->assertRaw('Hello world!');
    $this->assertNoCacheTag('foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.cacheable-response.early'));
    $this->assertResponse(500);
    $this->assertRaw('The controller result claims to be providing relevant cache metadata, but leaked metadata was detected. Please ensure you are not rendering content too early. Returned object class: Drupal\early_rendering_controller_test\CacheableTestResponse.');

    // Basic domain object: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.domain-object'));
    $this->assertResponse(200);
    $this->assertRaw('TestDomainObject');
    $this->assertNoCacheTag('foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.domain-object.early'));
    $this->assertResponse(200);
    $this->assertRaw('TestDomainObject');
    $this->assertNoCacheTag('foo');

    // Basic domain object with attachments: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.domain-object-with-attachments'));
    $this->assertResponse(200);
    $this->assertRaw('AttachmentsTestDomainObject');
    $this->assertNoCacheTag('foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.domain-object-with-attachments.early'));
    $this->assertResponse(500);
    $this->assertRaw('The controller result claims to be providing relevant cache metadata, but leaked metadata was detected. Please ensure you are not rendering content too early. Returned object class: Drupal\early_rendering_controller_test\AttachmentsTestDomainObject.');

    // Cacheable Response object: non-early & early.
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.cacheable-domain-object'));
    $this->assertResponse(200);
    $this->assertRaw('CacheableTestDomainObject');
    $this->assertNoCacheTag('foo');
    $this->drupalGet(Url::fromRoute('early_rendering_controller_test.cacheable-domain-object.early'));
    $this->assertResponse(500);
    $this->assertRaw('The controller result claims to be providing relevant cache metadata, but leaked metadata was detected. Please ensure you are not rendering content too early. Returned object class: Drupal\early_rendering_controller_test\CacheableTestDomainObject.');

    // The exceptions are expected. Do not interpret them as a test failure.
    // Not using File API; a potential error must trigger a PHP warning.
    unlink(\Drupal::root() . '/' . $this->siteDirectory . '/error.log');
  }

}
