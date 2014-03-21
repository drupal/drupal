<?php

/**
 * @file
 * Definition of Drupal\rest\test\ResourceTest.
 */

namespace Drupal\rest\Tests;

use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests the REST resource structure.
 */
class ResourceTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('hal', 'rest', 'entity_test');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Resource structure',
      'description' => 'Tests the structure of a REST resource',
      'group' => 'REST',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->config = \Drupal::config('rest.settings');

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
    $response = $this->httpRequest($this->entity->getSystemPath(), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse('404', 'HTTP response code is 404 when the resource does not define formats.');
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
    $response = $this->httpRequest($this->entity->getSystemPath(), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse('404', 'HTTP response code is 404 when the resource does not define authentication.');
    $this->curlClose();
  }

}
