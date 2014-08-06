<?php

/**
 * @file
 * Contains \Drupal\serialization\Tests\EntitySerializationTest.
 */

namespace Drupal\serialization\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Component\Utility\String;

/**
 * Tests that entities can be serialized to supported core formats.
 *
 * @group serialization
 */
class EntitySerializationTest extends NormalizerTestBase {

  /**
   * The test values.
   *
   * @var array
   */
  protected $values;

  /**
   * The test entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityBase
   */
  protected $entity;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer.
   */
  protected $serializer;

  /**
   * The class name of the test class.
   *
   * @var string
   */
  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  protected function setUp() {
    parent::setUp();

    // Create a test entity to serialize.
    $this->values = array(
      'name' => $this->randomMachineName(),
      'user_id' => \Drupal::currentUser()->id(),
      'field_test_text' => array(
        'value' => $this->randomMachineName(),
        'format' => 'full_html',
      ),
    );
    $this->entity = entity_create('entity_test_mulrev', $this->values);
    $this->entity->save();

    $this->serializer = $this->container->get('serializer');

    $this->installConfig(array('field'));
  }

  /**
   * Test the normalize function.
   */
  public function testNormalize() {
    $expected = array(
      'id' => array(
        array('value' => 1),
      ),
      'uuid' => array(
        array('value' => $this->entity->uuid()),
      ),
      'langcode' => array(
        array('value' => LanguageInterface::LANGCODE_NOT_SPECIFIED),
      ),
      'name' => array(
        array('value' => $this->values['name']),
      ),
      'type' => array(
        array('value' => 'entity_test_mulrev'),
      ),
      'user_id' => array(
        array('target_id' => $this->values['user_id']),
      ),
      'revision_id' => array(
        array('value' => 1),
      ),
      'field_test_text' => array(
        array(
          'value' => $this->values['field_test_text']['value'],
          'format' => $this->values['field_test_text']['format'],
        ),
      ),
    );

    $normalized = $this->serializer->normalize($this->entity);

    foreach (array_keys($expected) as $fieldName) {
      $this->assertEqual($expected[$fieldName], $normalized[$fieldName], "ComplexDataNormalizer produces expected array for $fieldName.");
    }
    $this->assertEqual(array_diff_key($normalized, $expected), array(), 'No unexpected data is added to the normalized array.');
  }

  /**
   * Test registered Serializer's entity serialization for core's formats.
   */
  public function testSerialize() {
    // Test that Serializer responds using the ComplexDataNormalizer and
    // JsonEncoder. The output of ComplexDataNormalizer::normalize() is tested
    // elsewhere, so we can just assume that it works properly here.
    $normalized = $this->serializer->normalize($this->entity, 'json');
    $expected = json_encode($normalized);
    // Test 'json'.
    $actual = $this->serializer->serialize($this->entity, 'json');
    $this->assertIdentical($actual, $expected, 'Entity serializes to JSON when "json" is requested.');
    $actual = $this->serializer->serialize($normalized, 'json');
    $this->assertIdentical($actual, $expected, 'A normalized array serializes to JSON when "json" is requested');
    // Test 'ajax'.
    $actual = $this->serializer->serialize($this->entity, 'ajax');
    $this->assertIdentical($actual, $expected, 'Entity serializes to JSON when "ajax" is requested.');
    $actual = $this->serializer->serialize($normalized, 'ajax');
    $this->assertIdentical($actual, $expected, 'A normalized array serializes to JSON when "ajax" is requested');

    // Generate the expected xml in a way that allows changes to entity property
    // order.
    $expected = array(
      'id' => '<id><value>' . $this->entity->id() . '</value></id>',
      'uuid' => '<uuid><value>' . $this->entity->uuid() . '</value></uuid>',
      'langcode' => '<langcode><value>' . LanguageInterface::LANGCODE_NOT_SPECIFIED . '</value></langcode>',
      'name' => '<name><value>' . $this->values['name'] . '</value></name>',
      'type' => '<type><value>entity_test_mulrev</value></type>',
      'user_id' => '<user_id><target_id>' . $this->values['user_id'] . '</target_id></user_id>',
      'revision_id' => '<revision_id><value>' . $this->entity->getRevisionId() . '</value></revision_id>',
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
    $actual = $this->serializer->serialize($this->entity, 'xml');
    $this->assertIdentical($actual, $expected);
    $actual = $this->serializer->serialize($normalized, 'xml');
    $this->assertIdentical($actual, $expected);
  }

  /**
   * Tests denormalization of an entity.
   */
  public function testDenormalize() {
    $normalized = $this->serializer->normalize($this->entity);

    foreach (array('json', 'xml') as $type) {
      $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, $type, array('entity_type' => 'entity_test_mulrev'));
      $this->assertTrue($denormalized instanceof $this->entityClass, String::format('Denormalized entity is an instance of @class', array('@class' => $this->entityClass)));
      $this->assertIdentical($denormalized->getEntityTypeId(), $this->entity->getEntityTypeId(), 'Expected entity type found.');
      $this->assertIdentical($denormalized->bundle(), $this->entity->bundle(), 'Expected entity bundle found.');
      $this->assertIdentical($denormalized->uuid(), $this->entity->uuid(), 'Expected entity UUID found.');
    }
  }
}
