<?php

/**
 * @file
 * Definition of Drupal\jsonld\Tests\JsonldNormalizerTest.
 */

namespace Drupal\jsonld\Tests;

use Drupal\config\Tests\ConfigEntityTest;
use Drupal\jsonld\JsonldNormalizer;
use Drupal\jsonld\Tests\JsonldNormalizerTestBase;

/**
 * Test the default JSON-LD normalizer.
 */
class JsonldNormalizerTest extends JsonldNormalizerTestBase {
  /**
   * The normalizer to be tested.
   */
  protected $normalizer;

  public static function getInfo() {
    return array(
      'name' => 'JSON-LD Normalizer',
      'description' => "Test the JSON-LD normalizer.",
      'group' => 'JSON-LD',
    );
  }

  /**
   * Add the normalizer to be tested.
   */
  function setUp() {
    parent::setUp();

    $this->normalizer = new JsonldNormalizer();
  }

  /**
   * Tests the supportsNormalization function.
   */
  public function testSupportsNormalization() {
    $function = 'JsonldNormalizer::supportsNormlization';
    $supportedFormat = 'jsonld';
    $unsupportedFormat = 'drupal_jsonld';
    $supportedEntity = entity_create('entity_test', array());
    $unsupportedEntity = new ConfigEntityTest();

    // Supported entity, supported format.
    $this->assertTrue($this->normalizer->supportsNormalization($supportedEntity, $supportedFormat), "$function returns TRUE for supported format.");
    // Supported entity, unsupported format.
    $this->assertFalse($this->normalizer->supportsNormalization($supportedEntity, $unsupportedFormat), "$function returns FALSE for unsupported format.");
    // Unsupported entity, supported format.
    $this->assertFalse($this->normalizer->supportsNormalization($unsupportedEntity, $supportedFormat), "$function returns FALSE for unsupported entity type.");
  }

}
