<?php

namespace Drupal\Tests\hal\Kernel;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntitySerializedField;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Tests HAL denormalization edge cases for EntityResource.
 *
 * @group hal
 */
class DenormalizeTest extends NormalizerTestBase {

  /**
   * Tests that the type link relation in incoming data is handled correctly.
   */
  public function testTypeHandling() {
    // Valid type.
    $data_with_valid_type = [
      '_links' => [
        'type' => [
          'href' => Url::fromUri('base:rest/type/entity_test/entity_test', ['absolute' => TRUE])->toString(),
        ],
      ],
    ];
    $denormalized = $this->serializer->denormalize($data_with_valid_type, $this->entityClass, $this->format);
    $this->assertEqual($this->entityClass, get_class($denormalized), 'Request with valid type results in creation of correct bundle.');

    // Multiple types.
    $data_with_multiple_types = [
      '_links' => [
        'type' => [
          [
            'href' => Url::fromUri('base:rest/types/foo', ['absolute' => TRUE])->toString(),
          ],
          [
            'href' => Url::fromUri('base:rest/type/entity_test/entity_test', ['absolute' => TRUE])->toString(),
          ],
        ],
      ],
    ];
    $denormalized = $this->serializer->denormalize($data_with_multiple_types, $this->entityClass, $this->format);
    $this->assertEqual($this->entityClass, get_class($denormalized), 'Request with multiple types results in creation of correct bundle.');

    // Invalid type.
    $data_with_invalid_type = [
      '_links' => [
        'type' => [
          'href' => Url::fromUri('base:rest/types/foo', ['absolute' => TRUE])->toString(),
        ],
      ],
    ];
    try {
      $this->serializer->denormalize($data_with_invalid_type, $this->entityClass, $this->format);
      $this->fail('Exception should be thrown when type is invalid.');
    }
    catch (UnexpectedValueException $e) {
      // Expected exception; just continue testing.
    }

    // No type.
    $data_with_no_type = [
      '_links' => [],
    ];
    try {
      $this->serializer->denormalize($data_with_no_type, $this->entityClass, $this->format);
      $this->fail('Exception should be thrown when no type is provided.');
    }
    catch (UnexpectedValueException $e) {
      // Expected exception; just continue testing.
    }
  }

  /**
   * Tests link relation handling with an invalid type.
   */
  public function testTypeHandlingWithInvalidType() {
    $data_with_invalid_type = [
      '_links' => [
        'type' => [
          'href' => Url::fromUri('base:rest/type/entity_test/entity_test_invalid', ['absolute' => TRUE])->toString(),
        ],
      ],
    ];

    $this->expectException(UnexpectedValueException::class);
    $this->serializer->denormalize($data_with_invalid_type, $this->entityClass, $this->format);
  }

  /**
   * Tests link relation handling with no types.
   */
  public function testTypeHandlingWithNoTypes() {
    $data_with_no_types = [
      '_links' => [
        'type' => [],
      ],
    ];

    $this->expectException(UnexpectedValueException::class);
    $this->serializer->denormalize($data_with_no_types, $this->entityClass, $this->format);
  }

  /**
   * Test that a field set to an empty array is different than an absent field.
   */
  public function testMarkFieldForDeletion() {
    // Add a default value for a field.
    $field = FieldConfig::loadByName('entity_test', 'entity_test', 'field_test_text');
    $field->setDefaultValue([['value' => 'Llama']]);
    $field->save();

    // Denormalize data that contains no entry for the field, and check that
    // the default value is present in the resulting entity.
    $data = [
      '_links' => [
        'type' => [
          'href' => Url::fromUri('base:rest/type/entity_test/entity_test', ['absolute' => TRUE])->toString(),
        ],
      ],
    ];
    $entity = $this->serializer->denormalize($data, $this->entityClass, $this->format);
    $this->assertEqual(1, $entity->field_test_text->count());
    $this->assertEqual('Llama', $entity->field_test_text->value);

    // Denormalize data that contains an empty entry for the field, and check
    // that the field is empty in the resulting entity.
    $data = [
      '_links' => [
        'type' => [
          'href' => Url::fromUri('base:rest/type/entity_test/entity_test', ['absolute' => TRUE])->toString(),
        ],
      ],
      'field_test_text' => [],
    ];
    $entity = $this->serializer->denormalize($data, get_class($entity), $this->format, ['target_instance' => $entity]);
    $this->assertEqual(0, $entity->field_test_text->count());
  }

  /**
   * Tests normalizing/denormalizing serialized columns.
   */
  public function testDenormalizeSerializedItem() {
    $entity = EntitySerializedField::create(['serialized' => 'boo']);
    $normalized = $this->serializer->normalize($entity, $this->format);
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The generic FieldItemNormalizer cannot denormalize string values for "value" properties of the "serialized" field (field item class: Drupal\entity_test\Plugin\Field\FieldType\SerializedItem).');
    $this->serializer->denormalize($normalized, EntitySerializedField::class, $this->format);
  }

  /**
   * Tests normalizing/denormalizing invalid custom serialized fields.
   */
  public function testDenormalizeInvalidCustomSerializedField() {
    $entity = EntitySerializedField::create(['serialized_long' => serialize(['Hello world!'])]);
    $normalized = $this->serializer->normalize($entity);
    $this->assertEquals($normalized['serialized_long'][0]['value'], ['Hello world!']);

    $normalized['serialized_long'][0]['value'] = 'boo';
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The generic FieldItemNormalizer cannot denormalize string values for "value" properties of the "serialized_long" field (field item class: Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem).');
    $this->serializer->denormalize($normalized, EntitySerializedField::class);
  }

  /**
   * Tests normalizing/denormalizing empty custom serialized fields.
   */
  public function testDenormalizeEmptyCustomSerializedField() {
    $entity = EntitySerializedField::create(['serialized_long' => serialize([])]);
    $normalized = $this->serializer->normalize($entity);
    $this->assertEquals([], $normalized['serialized_long'][0]['value']);

    $entity = $this->serializer->denormalize($normalized, EntitySerializedField::class);
    $this->assertEquals(serialize([]), $entity->get('serialized_long')->value);
  }

  /**
   * Tests normalizing/denormalizing valid custom serialized fields.
   */
  public function testDenormalizeValidCustomSerializedField() {
    $entity = EntitySerializedField::create(['serialized_long' => serialize(['key' => 'value'])]);
    $normalized = $this->serializer->normalize($entity);
    $this->assertEquals(['key' => 'value'], $normalized['serialized_long'][0]['value']);

    $entity = $this->serializer->denormalize($normalized, EntitySerializedField::class);

    $this->assertEquals(serialize(['key' => 'value']), $entity->get('serialized_long')->value);
  }

}
