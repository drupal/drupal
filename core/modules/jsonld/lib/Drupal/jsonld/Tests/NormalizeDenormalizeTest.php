<?php

/**
 * @file
 * Contains Drupal\jsonld\Tests\NormalizeDenormalizeTest.
 */

namespace Drupal\jsonld\Tests;

use Drupal\Core\Language\Language;
use Drupal\rdf\SiteSchema\SiteSchema;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Test the vendor specific JSON-LD normalizer.
 *
 * This is implemented as a WebTest because it requires use of the Entity API.
 */
class NormalizeDenormalizeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'jsonld', 'language', 'rdf');

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

    $setup_helper = new JsonldTestSetupHelper($this->container);
    $this->normalizers = $setup_helper->getNormalizers();

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
    $schema = new SiteSchema(SiteSchema::CONTENT_DEPLOYMENT);
    $bundle_uri = $schema->bundle('entity_test', 'entity_test')->getUri();
    $incoming_data = array(
      '@type' => $bundle_uri,
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

    // Test valid request.
    $entity = $this->normalizers['entity']->denormalize($incoming_data, 'Drupal\Core\Entity\EntityNG', static::$format);
    $this->assertEqual('entity_test', $entity->bundle(), "Denormalize creates entity with correct bundle.");
    $this->assertEqual($incoming_data['name']['en'], $entity->get('name')->getValue(), "Translatable field denormalized correctly in default language.");
    $this->assertEqual($incoming_data['name']['de'], $entity->getTranslation('de')->get('name')->getValue(), "Translatable field denormalized correctly in translation language.");
    $this->assertEqual($incoming_data['field_test_text']['und'], $entity->get('field_test_text')->getValue(), "Untranslatable field denormalized correctly.");

    // Test request without @type.
    unset($incoming_data['@type']);
    try {
      $this->normalizers['entity']->denormalize($incoming_data, 'Drupal\Core\Entity\EntityNG', static::$format);
      $this->fail('Trying to denormalize entity data without @type results in exception.');
    }
    catch (UnexpectedValueException $e) {
      $this->pass('Trying to denormalize entity data without @type results in exception.');
    }

    // Test request with @type that has no valid mapping.
    $incoming_data['@type'] = 'http://failing-uri.com/type';
    try {
      $this->normalizers['entity']->denormalize($incoming_data, 'Drupal\Core\Entity\EntityNG', static::$format);
      $this->fail('Trying to denormalize entity data with unrecognized @type results in exception.');
    }
    catch (UnexpectedValueException $e) {
      $this->pass('Trying to denormalize entity data with unrecognized @type results in exception.');
    }
  }

  /**
   * Get the Entity ID.
   *
   * @param \Drupal\Core\Entity\EntityNG $entity
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
