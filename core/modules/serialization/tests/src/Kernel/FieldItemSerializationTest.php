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
  protected static $modules = [
    'serialization',
    'system',
    'field',
    'entity_test',
    'text',
    'filter',
    'user',
    'field_normalization_test',
  ];

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
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
    FieldStorageConfig::create([
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_boolean',
      'type' => 'boolean',
      'cardinality' => 1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_boolean',
      'bundle' => 'entity_test_mulrev',
      'label' => 'Test boolean',
    ])->save();

    // Create a test entity to serialize.
    $this->values = [
      'name' => $this->randomMachineName(),
      'field_test_text' => [
        'value' => $this->randomMachineName(),
        'format' => 'full_html',
      ],
      'field_test_boolean' => [
        'value' => FALSE,
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
    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Field values for "uuid" must use an array structure');

    $normalized = $this->serializer->normalize($this->entity, 'json');

    // Change the UUID value to use the UUID directly. No array structure.
    $normalized['uuid'] = $normalized['uuid'][0]['value'];

    $this->serializer->denormalize($normalized, $this->entityClass, 'json');
  }

  /**
   * Tests a format-agnostic normalizer.
   *
   * @param string[] $test_modules
   *   The test modules to install.
   * @param string $format
   *   The format to test. (NULL results in the format-agnostic normalization.)
   *
   * @dataProvider providerTestCustomBooleanNormalization
   */
  public function testCustomBooleanNormalization(array $test_modules, $format) {
    // Asserts the entity contains the value we set.
    $this->assertFalse($this->entity->field_test_boolean->value);

    // Asserts normalizing the entity using core's 'serializer' service DOES
    // yield the value we set.
    $core_normalization = $this->container->get('serializer')->normalize($this->entity, $format);
    $this->assertFalse($core_normalization['field_test_boolean'][0]['value']);

    $assert_denormalization = function (array $normalization) use ($format) {
      $denormalized_entity = $this->container->get('serializer')->denormalize($normalization, EntityTestMulRev::class, $format, []);
      $this->assertInstanceOf(EntityTestMulRev::class, $denormalized_entity);
      $this->assertTrue($denormalized_entity->field_test_boolean->value);
    };

    // Asserts denormalizing the entity DOES yield the value we set:
    // - when using the detailed representation
    $core_normalization['field_test_boolean'][0]['value'] = TRUE;
    $assert_denormalization($core_normalization);
    // - and when using the shorthand representation
    $core_normalization['field_test_boolean'][0] = TRUE;
    $assert_denormalization($core_normalization);

    // Install test module that contains a high-priority alternative normalizer.
    $this->enableModules($test_modules);

    // Asserts normalizing the entity DOES NOT ANYMORE yield the value we set.
    $core_normalization = $this->container->get('serializer')->normalize($this->entity, $format);
    $this->assertSame('👎', $core_normalization['field_test_boolean'][0]['value']);

    // Asserts denormalizing the entity DOES NOT ANYMORE yield the value we set:
    // - when using the detailed representation
    $core_normalization['field_test_boolean'][0]['value'] = '👍';
    $assert_denormalization($core_normalization);
    // - and when using the shorthand representation
    $core_normalization['field_test_boolean'][0] = '👍';
    $assert_denormalization($core_normalization);
  }

  /**
   * Data provider.
   *
   * @return array
   *   Test cases.
   */
  public function providerTestCustomBooleanNormalization() {
    return [
      'Format-agnostic @FieldType-level normalizers SHOULD be able to affect the format-agnostic normalization' => [
        ['test_fieldtype_boolean_emoji_normalizer'],
        NULL,
      ],
      'Format-agnostic @DataType-level normalizers SHOULD be able to affect the format-agnostic normalization' => [
        ['test_datatype_boolean_emoji_normalizer'],
        NULL,
      ],
      'Format-agnostic @FieldType-level normalizers SHOULD be able to affect the JSON normalization' => [
        ['test_fieldtype_boolean_emoji_normalizer'],
        'json',
      ],
      'Format-agnostic @DataType-level normalizers SHOULD be able to affect the JSON normalization' => [
        ['test_datatype_boolean_emoji_normalizer'],
        'json',
      ],
      'Format-agnostic @FieldType-level normalizers SHOULD be able to affect the HAL+JSON normalization' => [
        ['test_fieldtype_boolean_emoji_normalizer'],
        'hal_json',
      ],
      'Format-agnostic @DataType-level normalizers SHOULD be able to affect the HAL+JSON normalization' => [
        ['test_datatype_boolean_emoji_normalizer', 'hal'],
        'hal_json',
      ],
      'Format-agnostic @FieldType-level normalizers SHOULD be able to affect the XML normalization' => [
        ['test_fieldtype_boolean_emoji_normalizer'],
        'xml',
      ],
      'Format-agnostic @DataType-level normalizers SHOULD be able to affect the XML normalization' => [
        ['test_datatype_boolean_emoji_normalizer', 'hal'],
        'xml',
      ],
    ];
  }

}
