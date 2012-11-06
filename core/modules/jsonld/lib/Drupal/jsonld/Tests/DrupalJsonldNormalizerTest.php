<?php

/**
 * @file
 * Definition of Drupal\jsonld\Tests\DrupalJsonldNormalizerTest.
 */

namespace Drupal\jsonld\Tests;

use Drupal\config\Tests\ConfigEntityTest;
use Drupal\Core\Language\Language;
use Drupal\jsonld\DrupalJsonldNormalizer;
use Drupal\jsonld\Tests\JsonldNormalizerTestBase;

/**
 * Test the vendor specific JSON-LD normalizer.
 */
class DrupalJsonldNormalizerTest extends JsonldNormalizerTestBase {
  /**
   * The normalizer to be tested.
   */
  protected $normalizer;

  public static function getInfo() {
    return array(
      'name' => 'Drupal JSON-LD Normalizer',
      'description' => "Test Drupal's vendor specific JSON-LD normalizer.",
      'group' => 'JSON-LD',
    );
  }

  /**
   * Add the normalizer to be tested.
   */
  function setUp() {
    parent::setUp();

    $this->normalizer = new DrupalJsonldNormalizer();
  }

  /**
   * Tests the supportsNormalization function.
   */
  public function testSupportsNormalization() {
    $function = 'DrupalJsonldNormalizer::supportsNormlization';
    $supportedFormat = 'drupal_jsonld';
    $unsupportedFormat = 'jsonld';
    $supportedEntity = entity_create('entity_test', array());
    $unsupportedEntity = new ConfigEntityTest();

    // Supported entity, supported format.
    $this->assertTrue($this->normalizer->supportsNormalization($supportedEntity, $supportedFormat), "$function returns TRUE for supported format.");
    // Supported entity, unsupported format.
    $this->assertFalse($this->normalizer->supportsNormalization($supportedEntity, $unsupportedFormat), "$function returns FALSE for unsupported format.");
    // Unsupported entity, supported format.
    $this->assertFalse($this->normalizer->supportsNormalization($unsupportedEntity, $supportedFormat), "$function returns FALSE for unsupported entity type.");
  }

  /**
   * Tests the normalize function.
   */
  public function testNormalize() {
    // Add German as a language.
    $language = new Language(array(
      'langcode' => 'de',
      'name' => 'Deutsch',
    ));
    language_save($language);

    // Create a German entity.
    $values = array(
      'langcode' => 'de',
      'name' => $this->randomName(),
      'user_id' => $GLOBALS['user']->uid,
      'field_test_text' => array(
        'value' => $this->randomName(),
        'format' => 'full_html',
      ),
    );
    // Array of translated values.
    $translationValues = array(
      'name' => $this->randomName(),
    );

    $entity = entity_create('entity_test', $values);
    $entity->save();
    // Add an English value for name property.
    $entity->getTranslation('en')->set('name', array(0 => array('value' => $translationValues['name'])));

    $expectedArray = array(
      '@id' => $this->getEntityId($entity),
      'uuid' => array(
        'und' => array(
          array(
            'value' => $entity->uuid()
          ),
        ),
      ),
      'user_id' => array(
        'de' => array(
          array(
            'value' => 1,
          ),
        ),
      ),
      'name' => array(
        'de' => array(
          array(
            'value' => $values['name'],
          ),
        ),
        'en' => array(
          array(
            'value' => $translationValues['name'],
          ),
        ),
      ),
      'field_test_text' => array(
        'und' => array(
          array(
            'value' => $values['field_test_text']['value'],
            'format' => $values['field_test_text']['format'],
          ),
        ),
      ),
    );

    $normalized = $this->normalizer->normalize($entity);
    // Test ordering. The @context and @id properties should always be first.
    $keys = array_keys($normalized);
    $this->assertEqual($keys[0], '@id', '@id and @context attributes placed correctly.');
    // Test @id value.
    $this->assertEqual($normalized['@id'], $expectedArray['@id'], '@id uses correct value.');
    // Test non-translatable field.
    $this->assertEqual($normalized['uuid'], $expectedArray['uuid'], 'Non-translatable fields are nested correctly.');
    // Test single-language translatable.
    $this->assertEqual($normalized['user_id'], $expectedArray['user_id'], 'Translatable field with single language value is nested correctly.');
    // Test multi-language translatable.
    $this->assertEqual($normalized['name'], $expectedArray['name'], 'Translatable field with multiple language values is nested correctly.');
    // Test multi-property untranslatable field.
    $this->assertEqual($normalized['field_test_text'], $expectedArray['field_test_text'], 'Field with properties is nested correctly.');
  }

}
