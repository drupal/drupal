<?php

namespace Drupal\Tests\serialization\Kernel;

use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Test field level normalization process.
 *
 * @group serialization
 */
class FieldItemSerializationTest extends NormalizerTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['serialization', 'system', 'field', 'entity_test', 'text', 'filter', 'user', 'field_normalization_test'];

  /**
   * The class name of the test class.
   *
   * @var string
   */
  protected $entityClass = 'Drupal\entity_test\Entity\EntityTestMulRev';

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Auto-create a field for testing default field values.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text_default',
      'type' => 'text',
      'cardinality' => 1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text_default',
      'bundle' => 'entity_test_mulrev',
      'label' => 'Test text-field with default',
      'default_value' => [
        [
          'value' => 'This is the default',
          'format' => 'full_html',
        ],
      ],
      'widget' => [
        'type' => 'text_textfield',
        'weight' => 0,
      ],
    ])->save();

    // Create a test entity to serialize.
    $this->values = [
      'name' => $this->randomMachineName(),
      'field_test_text' => [
        'value' => $this->randomMachineName(),
        'format' => 'full_html',
      ],
    ];
    $this->entity = EntityTestMulRev::create($this->values);
    $this->entity->save();

    $this->serializer = $this->container->get('serializer');

    $this->installConfig(['field']);
  }

  /**
   * Tests normalizing and denormalizing an entity with field item normalizer.
   */
  public function testFieldNormalizeDenormalize() {
    $normalized = $this->serializer->normalize($this->entity, 'json');

    $expected_field_value = $this->entity->field_test_text[0]->getValue()['value'] . '::silly_suffix';
    $this->assertEquals($expected_field_value, $normalized['field_test_text'][0]['value'], 'Text field item normalized');
    $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, 'json');

    $this->assertEquals($denormalized->field_test_text[0]->getValue(), $this->entity->field_test_text[0]->getValue(), 'Text field item denormalized.');
    $this->assertEquals($denormalized->field_test_text_default[0]->getValue(), $this->entity->field_test_text_default[0]->getValue(), 'Text field item with default denormalized.');

    // Unset the values for text field that has a default value.
    unset($normalized['field_test_text_default']);
    $denormalized_without_all_fields = $this->serializer->denormalize($normalized, $this->entityClass, 'json');
    // Check that denormalized entity is still the same even if not all fields
    // are not provided.
    $this->assertEquals($denormalized_without_all_fields->field_test_text[0]->getValue(), $this->entity->field_test_text[0]->getValue(), 'Text field item denormalized.');
    // Even though field_test_text_default value was unset before
    // denormalization it should still have the default values for the field.
    $this->assertEquals($denormalized_without_all_fields->field_test_text_default[0]->getValue(), $this->entity->field_test_text_default[0]->getValue(), 'Text field item with default denormalized.');
  }

  /**
   * Tests denormalizing using a scalar field value.
   */
  public function testFieldDenormalizeWithScalarValue() {
    $this->setExpectedException(UnexpectedValueException::class, 'Field values for "uuid" must use an array structure');

    $normalized = $this->serializer->normalize($this->entity, 'json');

    // Change the UUID value to use the UUID directly. No array structure.
    $normalized['uuid'] = $normalized['uuid'][0]['value'];

    $this->serializer->denormalize($normalized, $this->entityClass, 'json');
  }

}
