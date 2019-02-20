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
    $this->assertEqual(get_class($denormalized), $this->entityClass, 'Request with valid type results in creation of correct bundle.');

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
    $this->assertEqual(get_class($denormalized), $this->entityClass, 'Request with multiple types results in creation of correct bundle.');

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
      $this->pass('Exception thrown when type is invalid.');
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
      $this->pass('Exception thrown when no type is provided.');
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

    $this->setExpectedException(UnexpectedValueException::class);
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

    $this->setExpectedException(UnexpectedValueException::class);
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
    $this->assertEqual($entity->field_test_text->count(), 1);
    $this->assertEqual($entity->field_test_text->value, 'Llama');

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
    $this->assertEqual($entity->field_test_text->count(), 0);
  }

}
