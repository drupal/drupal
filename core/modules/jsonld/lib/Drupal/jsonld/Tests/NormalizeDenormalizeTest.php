<?php

/**
 * @file
 * Contains Drupal\jsonld\Tests\NormalizeDenormalizeTest.
 */

namespace Drupal\jsonld\Tests;

use Drupal\Core\Language\Language;
use Drupal\jsonld\JsonldEncoder;
use Drupal\jsonld\JsonldEntityNormalizer;
use Drupal\jsonld\JsonldEntityReferenceNormalizer;
use Drupal\jsonld\JsonldFieldItemNormalizer;
use Drupal\simpletest\WebTestBase;
use Symfony\Component\Serializer\Serializer;

/**
 * Test the vendor specific JSON-LD normalizer.
 */
class NormalizeDenormalizeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'entity_test');

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
      'name' => 'Normalize/Denormalize Test',
      'description' => "Test that entities can be normalized/denormalized in JSON-LD.",
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
    $serializer = new Serializer($this->normalizers, array(new JsonldEncoder()));
    $this->normalizers['entity']->setSerializer($serializer);

    // Add German as a language.
    $language = new Language(array(
      'langcode' => 'de',
      'name' => 'Deutsch',
    ));
    language_save($language);
  }

  /**
   * Tests the normalize function.
   */
  public function testNormalize() {
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

  function testDenormalize() {
    $incomingData = array(
      '@type' => url('jsonld-test/content-staging/entity_test/entity_test', array('absolute' => TRUE)),
      'name' => array(
        'en' => array(
          array(
            'value' => $this->randomName(),
          ),
        ),
        'de' => array(
          array(
            'value' => $this->randomName(),
          ),
        ),
      ),
      'field_test_text' => array(
        'und' => array(
          array(
            'value' => $this->randomName(),
            'format' => 'full_html',
          ),
        ),
      ),
    );

    $entity = $this->normalizers['entity']->denormalize($incomingData, 'Drupal\Core\Entity\EntityNG', static::$format);
    $this->assertEqual('entity_test', $entity->bundle(), "Denormalize creates entity with correct bundle.");
    $this->assertEqual($incomingData['name']['en'], $entity->get('name')->getValue(), "Translatable field denormalized correctly in default language.");
    $this->assertEqual($incomingData['name']['de'], $entity->getTranslation('de')->get('name')->getValue(), "Translatable field denormalized correctly in translation language.");
    $this->assertEqual($incomingData['field_test_text']['und'], $entity->get('field_test_text')->getValue(), "Untranslatable field denormalized correctly.");
  }

  /**
   * Get the Entity ID.
   *
   * @param Drupal\Core\Entity\EntityNG $entity
   *   Entity to get URI for.
   *
   * @return string
   *   Return the entity URI.
   */
  protected function getEntityId($entity) {
    global $base_url;
    $uriInfo = $entity->uri();
    return $base_url . '/' . $uriInfo['path'];
  }

}
