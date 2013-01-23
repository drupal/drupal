<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Serialization\EntitySerializationTest.
 */

namespace Drupal\system\Tests\Serialization;

use Drupal\simpletest\WebTestBase;
use Symfony\Component\Serializer\Serializer;
use Drupal\Core\Serialization\JsonEncoder;
use Drupal\Core\Serialization\ComplexDataNormalizer;
use Drupal\Core\Serialization\TypedDataNormalizer;

/**
 * Tests entity normalization and serialization of supported core formats.
 */
class EntitySerializationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  /**
   * The test values.
   *
   * @var array
   */
  protected $values;

  /**
   * The test entity.
   *
   * @var \Drupal\Core\Entity\EntityNG
   */
  protected $entity;

  public static function getInfo() {
    return array(
      'name' => 'Entity serialization tests',
      'description' => 'Tests that entities can be serialized to supported core formats.',
      'group' => 'Serialization',
    );
  }

  protected function setUp() {
    parent::setUp();

    // Create a test entity to serialize.
    $this->values = array(
      'name' => $this->randomName(),
      'user_id' => $GLOBALS['user']->uid,
      'field_test_text' => array(
        'value' => $this->randomName(),
        'format' => 'full_html',
      ),
    );
    $this->entity = entity_create('entity_test_mulrev', $this->values);
    $this->entity->save();
  }

  /**
   * Test the normalize function.
   */
  public function testNormalize() {
    // Set up the serializer.
    $encoders = array(new JsonEncoder());
    $normalizers = array(new ComplexDataNormalizer(), new TypedDataNormalizer());
    $serializer = new Serializer($normalizers, $encoders);

    $expected = array(
      'id' => array(
        array('value' => 1),
      ),
      'revision_id' => array(
        array('value' => 1),
      ),
      'uuid' => array(
        array('value' => $this->entity->uuid()),
      ),
      'langcode' => array(
        array('value' => LANGUAGE_NOT_SPECIFIED),
      ),
      'default_langcode' => array(NULL),
      'name' => array(
        array('value' => $this->values['name']),
      ),
      'user_id' => array(
        array('value' => $this->values['user_id']),
      ),
      'field_test_text' => array(
        array(
          'value' => $this->values['field_test_text']['value'],
          'format' => $this->values['field_test_text']['format'],
        ),
      ),
    );

    $normalized = $serializer->normalize($this->entity);

    foreach (array_keys($expected) as $fieldName) {
      $this->assertEqual($expected[$fieldName], $normalized[$fieldName], "ComplexDataNormalizer produces expected array for $fieldName.");
    }
    $this->assertEqual(array_diff_key($normalized, $expected), array(), 'No unexpected data is added to the normalized array.');
  }

  /**
   * Test registered Serializer's entity serialization for core's formats.
   */
  public function testSerialize() {
    $serializer = drupal_container()->get('serializer');
    // Test that Serializer responds using the ComplexDataNormalizer and
    // JsonEncoder. The output of ComplexDataNormalizer::normalize() is tested
    // elsewhere, so we can just assume that it works properly here.
    $normalized = $serializer->normalize($this->entity, 'json');
    $expected = json_encode($normalized);
    // Test 'json'.
    $actual = $serializer->serialize($this->entity, 'json');
    $this->assertIdentical($actual, $expected, 'Entity serializes to JSON when "json" is requested.');
    $actual = $serializer->serialize($normalized, 'json');
    $this->assertIdentical($actual, $expected, 'A normalized array serializes to JSON when "json" is requested');
    // Test 'ajax'.
    $actual = $serializer->serialize($this->entity, 'ajax');
    $this->assertIdentical($actual, $expected, 'Entity serializes to JSON when "ajax" is requested.');
    $actual = $serializer->serialize($normalized, 'ajax');
    $this->assertIdentical($actual, $expected, 'A normalized array serializes to JSON when "ajax" is requested');

    // Generate the expected xml in a way that allows changes to entity property
    // order.
    $expected = array(
      'id' => '<id><value>' . $this->entity->id() . '</value></id>',
      'revision_id' => '<revision_id><value>' . $this->entity->getRevisionId() . '</value></revision_id>',
      'uuid' => '<uuid><value>' . $this->entity->uuid() . '</value></uuid>',
      'langcode' => '<langcode><value>' . LANGUAGE_NOT_SPECIFIED . '</value></langcode>',
      'default_langcode' => '<default_langcode><value/></default_langcode>',
      'name' => '<name><value>' . $this->values['name'] . '</value></name>',
      'user_id' => '<user_id><value>' . $this->values['user_id'] . '</value></user_id>',
      'field_test_text' => '<field_test_text><value>' . $this->values['field_test_text']['value'] . '</value><format>' . $this->values['field_test_text']['format'] . '</format></field_test_text>',
    );
    // Sort it in the same order as normalised.
    $expected = array_merge($normalized, $expected);
    // Add header and footer.
    array_unshift($expected, '<?xml version="1.0"?>' . PHP_EOL . '<response>');
    $expected[] = '</response>' . PHP_EOL;
    // Reduced the array to a string.
    $expected = implode('', $expected);
    // Test 'xml'. The output should match that of Symfony's XmlEncoder.
    $actual = $serializer->serialize($this->entity, 'xml');
    $this->assertIdentical($actual, $expected);
    $actual = $serializer->serialize($normalized, 'xml');
    $this->assertIdentical($actual, $expected);
  }
}
