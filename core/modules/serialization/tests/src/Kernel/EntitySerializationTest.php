<?php

namespace Drupal\Tests\serialization\Kernel;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\entity_test\Entity\EntityTestComputedField;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\entity_test\Entity\EntitySerializedField;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\filter\Entity\FilterFormat;
use Drupal\serialization\Normalizer\CacheableNormalizerInterface;

/**
 * Tests that entities can be serialized to supported core formats.
 *
 * @group serialization
 */
class EntitySerializationTest extends NormalizerTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'serialization',
    'system',
    'field',
    'entity_test',
    'text',
    'filter',
    'user',
    'entity_serialization_test',
  ];

  /**
   * The test values.
   *
   * @var array
   */
  protected $values;

  /**
   * The test entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The class name of the test class.
   *
   * @var string
   */
  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // User create needs sequence table.
    $this->installSchema('system', ['sequences']);

    FilterFormat::create([
      'format' => 'my_text_format',
      'name' => 'My Text Format',
      'filters' => [
        'filter_html' => [
          'module' => 'filter',
          'status' => TRUE,
          'weight' => 10,
          'settings' => [
            'allowed_html' => '<p>',
          ],
        ],
        'filter_autop' => [
          'module' => 'filter',
          'status' => TRUE,
          'weight' => 10,
          'settings' => [],
        ],
      ],
    ])->save();

    // Create a test user to use as the entity owner.
    $this->user = \Drupal::entityTypeManager()->getStorage('user')->create([
      'name' => 'serialization_test_user',
      'mail' => 'foo@example.com',
      'pass' => '123456',
    ]);
    $this->user->save();

    // Create a test entity to serialize.
    $test_text_value = $this->randomMachineName();
    $this->values = [
      'name' => $this->randomMachineName(),
      'user_id' => $this->user->id(),
      'field_test_text' => [
        'value' => $test_text_value,
        'format' => 'my_text_format',
      ],
    ];
    $this->entity = EntityTestMulRev::create($this->values);
    $this->entity->save();

    $this->serializer = $this->container->get('serializer');

    $this->installConfig(['field']);
  }

  /**
   * Tests the normalize function.
   */
  public function testNormalize() {
    $expected = [
      'id' => [
        ['value' => 1],
      ],
      'uuid' => [
        ['value' => $this->entity->uuid()],
      ],
      'langcode' => [
        ['value' => 'en'],
      ],
      'name' => [
        ['value' => $this->values['name']],
      ],
      'type' => [
        ['value' => 'entity_test_mulrev'],
      ],
      'created' => [
        [
          'value' => (new \DateTime())->setTimestamp((int) $this->entity->get('created')->value)->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'format' => \DateTime::RFC3339,
        ],
      ],
      'user_id' => [
        [
          // id() will return the string value as it comes from the database.
          'target_id' => (int) $this->user->id(),
          'target_type' => $this->user->getEntityTypeId(),
          'target_uuid' => $this->user->uuid(),
          'url' => $this->user->toUrl()->toString(),
        ],
      ],
      'revision_id' => [
        ['value' => 1],
      ],
      'default_langcode' => [
        ['value' => TRUE],
      ],
      'revision_translation_affected' => [
        ['value' => TRUE],
      ],
      'non_rev_field' => [],
      'non_mul_field' => [],
      'field_test_text' => [
        [
          'value' => $this->values['field_test_text']['value'],
          'format' => $this->values['field_test_text']['format'],
          'processed' => "<p>{$this->values['field_test_text']['value']}</p>",
        ],
      ],
    ];

    $normalized = $this->serializer->normalize($this->entity);

    foreach (array_keys($expected) as $fieldName) {
      $this->assertSame($expected[$fieldName], $normalized[$fieldName], "Normalization produces expected array for $fieldName.");
    }
    $this->assertEquals([], array_diff_key($normalized, $expected), 'No unexpected data is added to the normalized array.');
  }

  /**
   * Tests user normalization with some default access controls overridden.
   *
   * @see entity_serialization_test.module
   */
  public function testUserNormalize() {
    // Test password isn't available.
    $normalized = $this->serializer->normalize($this->user);

    $this->assertArrayNotHasKey('pass', $normalized);
    $this->assertArrayNotHasKey('mail', $normalized);

    // Test again using our test user, so that our access control override will
    // allow password viewing.
    $normalized = $this->serializer->normalize($this->user, NULL, ['account' => $this->user]);

    // The key 'pass' will now exist, but the password value should be
    // normalized to NULL.
    $this->assertSame([NULL], $normalized['pass'], '"pass" value is normalized to [NULL]');
  }

  /**
   * Tests entity serialization for core's formats by a registered Serializer.
   */
  public function testSerialize() {
    // Test that Serializer responds using the ComplexDataNormalizer and
    // JsonEncoder. The output of ComplexDataNormalizer::normalize() is tested
    // elsewhere, so we can just assume that it works properly here.
    $normalized = $this->serializer->normalize($this->entity, 'json');
    $expected = Json::encode($normalized);
    // Test 'json'.
    $actual = $this->serializer->serialize($this->entity, 'json');
    $this->assertSame($expected, $actual, 'Entity serializes to JSON when "json" is requested.');
    $actual = $this->serializer->serialize($normalized, 'json');
    $this->assertSame($expected, $actual, 'A normalized array serializes to JSON when "json" is requested');
    // Test 'ajax'.
    $actual = $this->serializer->serialize($this->entity, 'ajax');
    $this->assertSame($expected, $actual, 'Entity serializes to JSON when "ajax" is requested.');
    $actual = $this->serializer->serialize($normalized, 'ajax');
    $this->assertSame($expected, $actual, 'A normalized array serializes to JSON when "ajax" is requested');

    // Generate the expected xml in a way that allows changes to entity property
    // order.
    $expected_created = [
      'value' => DateTimePlus::createFromTimestamp($this->entity->created->value, 'UTC')->format(\DateTime::RFC3339),
      'format' => \DateTime::RFC3339,
    ];

    $expected = [
      'id' => '<id><value>' . $this->entity->id() . '</value></id>',
      'uuid' => '<uuid><value>' . $this->entity->uuid() . '</value></uuid>',
      'langcode' => '<langcode><value>en</value></langcode>',
      'name' => '<name><value>' . $this->values['name'] . '</value></name>',
      'type' => '<type><value>entity_test_mulrev</value></type>',
      'created' => '<created><value>' . $expected_created['value'] . '</value><format>' . $expected_created['format'] . '</format></created>',
      'user_id' => '<user_id><target_id>' . $this->user->id() . '</target_id><target_type>' . $this->user->getEntityTypeId() . '</target_type><target_uuid>' . $this->user->uuid() . '</target_uuid><url>' . $this->user->toUrl()->toString() . '</url></user_id>',
      'revision_id' => '<revision_id><value>' . $this->entity->getRevisionId() . '</value></revision_id>',
      'default_langcode' => '<default_langcode><value>1</value></default_langcode>',
      'revision_translation_affected' => '<revision_translation_affected><value>1</value></revision_translation_affected>',
      'non_mul_field' => '<non_mul_field/>',
      'non_rev_field' => '<non_rev_field/>',
      'field_test_text' => '<field_test_text><value>' . $this->values['field_test_text']['value'] . '</value><format>' . $this->values['field_test_text']['format'] . '</format><processed><![CDATA[<p>' . $this->values['field_test_text']['value'] . '</p>]]></processed></field_test_text>',
    ];
    // Sort it in the same order as normalized.
    $expected = array_merge($normalized, $expected);
    // Add header and footer.
    array_unshift($expected, '<?xml version="1.0"?>' . PHP_EOL . '<response>');
    $expected[] = '</response>' . PHP_EOL;
    // Reduced the array to a string.
    $expected = implode('', $expected);
    // Test 'xml'. The output should match that of Symfony's XmlEncoder.
    $actual = $this->serializer->serialize($this->entity, 'xml');
    $this->assertSame($expected, $actual);
    $actual = $this->serializer->serialize($normalized, 'xml');
    $this->assertSame($expected, $actual);
  }

  /**
   * Tests denormalization of an entity.
   */
  public function testDenormalize() {
    $normalized = $this->serializer->normalize($this->entity);

    foreach (['json', 'xml'] as $type) {
      $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, $type, ['entity_type' => 'entity_test_mulrev']);
      $this->assertInstanceOf($this->entityClass, $denormalized);
      $this->assertSame($this->entity->getEntityTypeId(), $denormalized->getEntityTypeId(), 'Expected entity type found.');
      $this->assertSame($this->entity->bundle(), $denormalized->bundle(), 'Expected entity bundle found.');
      $this->assertSame($this->entity->uuid(), $denormalized->uuid(), 'Expected entity UUID found.');
    }
  }

  /**
   * Tests denormalizing serialized columns.
   */
  public function testDenormalizeSerializedItem() {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The generic FieldItemNormalizer cannot denormalize string values for "value" properties of the "serialized" field (field item class: Drupal\entity_test\Plugin\Field\FieldType\SerializedItem).');
    $this->serializer->denormalize([
      'serialized' => [
        [
          'value' => 'boo',
        ],
      ],
      'type' => 'entity_test_serialized_field',
    ], EntitySerializedField::class);
  }

  /**
   * Tests normalizing/denormalizing custom serialized columns.
   */
  public function testDenormalizeCustomSerializedItem() {
    $entity = EntitySerializedField::create(['serialized_text' => serialize(['Hello world!'])]);
    $normalized = $this->serializer->normalize($entity);
    $this->assertEquals(['Hello world!'], $normalized['serialized_text'][0]['value']);
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The generic FieldItemNormalizer cannot denormalize string values for "value" properties of the "serialized_text" field (field item class: Drupal\entity_test\Plugin\Field\FieldType\SerializedPropertyItem).');
    $this->serializer->denormalize([
      'serialized_text' => [
        [
          'value' => 'boo',
        ],
      ],
      'type' => 'entity_test_serialized_field',
    ], EntitySerializedField::class);
  }

  /**
   * Tests normalizing/denormalizing invalid custom serialized fields.
   */
  public function testDenormalizeInvalidCustomSerializedField() {
    $entity = EntitySerializedField::create(['serialized_long' => serialize(['Hello world!'])]);
    $normalized = $this->serializer->normalize($entity);
    $this->assertEquals(['Hello world!'], $normalized['serialized_long'][0]['value']);
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The generic FieldItemNormalizer cannot denormalize string values for "value" properties of the "serialized_long" field (field item class: Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem).');
    $this->serializer->denormalize([
      'serialized_long' => [
        [
          'value' => 'boo',
        ],
      ],
      'type' => 'entity_test_serialized_field',
    ], EntitySerializedField::class);
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

  /**
   * Tests normalizing/denormalizing using string values.
   */
  public function testDenormalizeStringValue() {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The generic FieldItemNormalizer cannot denormalize string values for "value" properties of the "serialized_long" field (field item class: Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem).');
    $this->serializer->denormalize([
      'serialized_long' => ['boo'],
      'type' => 'entity_test_serialized_field',
    ], EntitySerializedField::class);
  }

  /**
   * Tests normalizing cacheable computed field.
   */
  public function testCacheableComputedField() {
    $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY] = new CacheableMetadata();
    $entity = EntityTestComputedField::create();
    $normalized = $this->serializer->normalize($entity, NULL, $context);
    $this->assertEquals('computed test cacheable string field', $normalized['computed_test_cacheable_string_field'][0]['value']);
    $this->assertInstanceOf(CacheableDependencyInterface::class, $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY]);
    // See \Drupal\entity_test\Plugin\Field\ComputedTestCacheableStringItemList::computeValue().
    $this->assertEquals($context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY]->getCacheContexts(), ['url.query_args:computed_test_cacheable_string_field']);
    $this->assertEquals($context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY]->getCacheTags(), ['field:computed_test_cacheable_string_field']);
    $this->assertEquals($context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY]->getCacheMaxAge(), 800);
  }

}
