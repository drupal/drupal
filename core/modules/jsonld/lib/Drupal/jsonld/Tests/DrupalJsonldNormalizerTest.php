<?php

/**
 * @file
 * Definition of Drupal\jsonld\Tests\DrupalJsonldNormalizerTest.
 */

namespace Drupal\jsonld\Tests;

use Drupal\config\Tests\ConfigEntityTest;
use Drupal\Core\Language\Language;
use Drupal\jsonld\JsonldEntityNormalizer;
use Drupal\jsonld\JsonldEntityReferenceNormalizer;
use Drupal\jsonld\JsonldFieldItemNormalizer;
use Drupal\jsonld\Tests\JsonldNormalizerTestBase;
use Symfony\Component\Serializer\Serializer;

/**
 * Test the vendor specific JSON-LD normalizer.
 */
class DrupalJsonldNormalizerTest extends JsonldNormalizerTestBase {

  /**
   * The format being tested.
   */
  protected static $format = 'drupal_jsonld';

  /**
   * The Normalizers to be tested.
   */
  protected $normalizers;

  public static function getInfo() {
    return array(
      'name' => 'vnd.drupal.ld+json Normalization',
      'description' => "Test Drupal's vendor specific JSON-LD normalizer.",
      'group' => 'JSON-LD',
    );
  }

  /**
   * Add the normalizer to be tested.
   */
  function setUp() {
    parent::setUp();

    $this->normalizers = array(
      'entityreference' => new JsonldEntityReferenceNormalizer(),
      'field_item' => new JsonldFieldItemNormalizer(),
      'entity' => new JsonldEntityNormalizer(),
    );
    $serializer = new Serializer($this->normalizers);
    $this->normalizers['entity']->setSerializer($serializer);
  }

  /**
   * Tests the supportsNormalization function.
   */
  public function testSupportsNormalization() {
    $format = static::$format;
    $supportedEntity = entity_create('entity_test', array());
    $unsupportedEntity = new ConfigEntityTest();
    $field = $supportedEntity->get('uuid');
    $entityreferenceField = $supportedEntity->get('user_id');

    // Supported entity.
    $this->assertTrue($this->normalizers['entity']->supportsNormalization($supportedEntity, static::$format), "Entity normalization is supported for $format on content entities.");
    // Unsupported entity.
    $this->assertFalse($this->normalizers['entity']->supportsNormalization($unsupportedEntity, static::$format), "Normalization is not supported for other entity types.");

    // Field item.
    $this->assertTrue($this->normalizers['field_item']->supportsNormalization($field->offsetGet(0), static::$format), "Field item normalization is supported for $format.");
    // Entity reference field item.
    $this->assertTrue($this->normalizers['entityreference']->supportsNormalization($entityreferenceField->offsetGet(0), static::$format), "Entity reference field item normalization is supported for $format.");
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
            'value' => $entity->uuid(),
          ),
        ),
      ),
      'user_id' => array(
        'de' => array(
          array(
            '@id' => url('user/' . $values['user_id'], array('absolute' => TRUE)),
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

    $normalized = $this->normalizers['entity']->normalize($entity, static::$format);
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
