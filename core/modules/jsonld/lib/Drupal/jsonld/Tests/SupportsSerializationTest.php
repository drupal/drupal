<?php

/**
 * @file
 * Contains Drupal\jsonld\Tests\SupportsSerializationTest.
 */

namespace Drupal\jsonld\Tests;

use Drupal\config\Tests\ConfigEntityTest;
use Drupal\simpletest\WebTestBase;

/**
 * Test the vendor specific JSON-LD normalizer.
 */
class SupportsSerializationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'jsonld');

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
      'name' => 'Supports class/format serialization test',
      'description' => "Test that normalizers and encoders support expected classes and formats.",
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
   * Tests the supportsDenormalization function.
   */
  public function testSupportsDenormalization() {
    $format = static::$format;
    $data = array();
    $supportedEntityClass = 'Drupal\Core\Entity\EntityNG';
    $unsupportedEntityClass = 'Drupal\config\Tests\ConfigEntityTest';
    $fieldClass = 'Drupal\Core\Entity\Field\Type\StringItem';
    $entityreferenceFieldClass = 'Drupal\Core\Entity\Field\Type\EntityReferenceItem';

    // Supported entity.
    $this->assertTrue($this->normalizers['entity']->supportsDenormalization($data, $supportedEntityClass, static::$format), "Entity denormalization is supported for $format on content entities.");
    // Unsupported entity.
    $this->assertFalse($this->normalizers['entity']->supportsDenormalization($data, $unsupportedEntityClass, static::$format), "Denormalization is not supported for other entity types.");

    // Field item.
    $this->assertTrue($this->normalizers['field_item']->supportsDenormalization($data, $fieldClass, static::$format), "Field item denormalization is supported for $format.");
    // Entity reference field item.
    $this->assertTrue($this->normalizers['entityreference']->supportsDenormalization($data, $entityreferenceFieldClass, static::$format), "Entity reference field item denormalization is supported for $format.");
  }

}
