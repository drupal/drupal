<?php

/**
 * @file
 * Definition of Drupal\rest\test\ResourceTest.
 */

namespace Drupal\rest\Tests;

use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests the structure of a REST resource.
 *
 * @group rest
 */
class ResourceTest extends RESTTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('hal', 'rest', 'entity_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->config = $this->config('rest.settings');

    // Create an entity programmatically.
    $this->entity = $this->entityCreate('entity_test');
    $this->entity->save();
  }

  /**
   * Tests that a resource without formats cannot be enabled.
   */
  public function testFormats() {
    $settings = array(
      'entity:entity_test' => array(
        'GET' => array(
          'supported_auth' => array(
            'basic_auth',
          ),
        ),
      ),
    );

    // Attempt to enable the resource.
    $this->config->set('resources', $settings);
    $this->config->save();
    $this->rebuildCache();

    // Verify that accessing the resource returns 401.
    $response = $this->httpRequest($this->entity->urlInfo(), 'GET', NULL, $this->defaultMimeType);
    // AcceptHeaderMatcher considers the canonical, non-REST route a match, but
    // a lower quality one: no format restrictions means there's always a match,
    // and hence when there is no matching REST route, the non-REST route is
    // used, but it can't render into application/hal+json, so it returns a 406.
    $this->assertResponse('406', 'HTTP response code is 406 when the resource does not define formats, because it falls back to the canonical, non-REST route.');
    $this->curlClose();
  }

  /**
   * Tests that a resource without authentication cannot be enabled.
   */
  public function testAuthentication() {
    $settings = array(
      'entity:entity_test' => array(
        'GET' => array(
          'supported_formats' => array(
            'hal_json',
          ),
        ),
      ),
    );

    // Attempt to enable the resource.
    $this->config->set('resources', $settings);
    $this->config->save();
    $this->rebuildCache();

    // Verify that accessing the resource returns 401.
    $response = $this->httpRequest($this->entity->urlInfo(), 'GET', NULL, $this->defaultMimeType);
    // AcceptHeaderMatcher considers the canonical, non-REST route a match, but
    // a lower quality one: no format restrictions means there's always a match,
    // and hence when there is no matching REST route, the non-REST route is
    // used, but it can't render into application/hal+json, so it returns a 406.
    $this->assertResponse('406', 'HTTP response code is 406 when the resource does not define formats, because it falls back to the canonical, non-REST route.');
    $this->curlClose();
  }

}
