<?php

namespace Drupal\Tests\jsonapi\Kernel\Context;

use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;

/**
 * @coversDefaultClass \Drupal\jsonapi\Context\FieldResolver
 * @group jsonapi
 * @group #slow
 *
 * @internal
 */
class FieldResolverTest extends JsonapiKernelTestBase {

  protected static $modules = [
    'entity_test',
    'jsonapi_test_field_aliasing',
    'jsonapi_test_field_filter_access',
    'serialization',
    'field',
    'text',
    'user',
  ];

  /**
   * The subject under test.
   *
   * @var \Drupal\jsonapi\Context\FieldResolver
   */
  protected $sut;

  /**
   * The JSON:API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_with_bundle');
    $this->sut = \Drupal::service('jsonapi.field_resolver');

    $this->makeBundle('bundle1');
    $this->makeBundle('bundle2');
    $this->makeBundle('bundle3');

    $this->makeField('string', 'field_test1', 'entity_test_with_bundle', ['bundle1']);
    $this->makeField('string', 'field_test2', 'entity_test_with_bundle', ['bundle1']);
    $this->makeField('string', 'field_test3', 'entity_test_with_bundle', ['bundle2', 'bundle3']);

    // Provides entity reference fields.
    $settings = ['target_type' => 'entity_test_with_bundle'];
    $this->makeField('entity_reference', 'field_test_ref1', 'entity_test_with_bundle', ['bundle1'], $settings, [
      'handler_settings' => [
        'target_bundles' => ['bundle2', 'bundle3'],
      ],
    ]);
    $this->makeField('entity_reference', 'field_test_ref2', 'entity_test_with_bundle', ['bundle1'], $settings);
    $this->makeField('entity_reference', 'field_test_ref3', 'entity_test_with_bundle', ['bundle2', 'bundle3'], $settings);

    // Add a field with multiple properties.
    $this->makeField('text', 'field_test_text', 'entity_test_with_bundle', ['bundle1', 'bundle2']);

    // Add two fields that have different internal names but have the same
    // public name.
    $this->makeField('entity_reference', 'field_test_alias_a', 'entity_test_with_bundle', ['bundle2'], $settings);
    $this->makeField('entity_reference', 'field_test_alias_b', 'entity_test_with_bundle', ['bundle3'], $settings);

    $this->resourceTypeRepository = $this->container->get('jsonapi.resource_type.repository');
  }

  /**
   * @covers ::resolveInternalEntityQueryPath
   * @dataProvider resolveInternalIncludePathProvider
   */
  public function testResolveInternalIncludePath($expect, $external_path, $entity_type_id = 'entity_test_with_bundle', $bundle = 'bundle1') {
    $path_parts = explode('.', $external_path);
    $resource_type = $this->resourceTypeRepository->get($entity_type_id, $bundle);
    $this->assertEquals($expect, $this->sut->resolveInternalIncludePath($resource_type, $path_parts));
  }

  /**
   * Provides test cases for resolveInternalEntityQueryPath.
   */
  public function resolveInternalIncludePathProvider() {
    return [
      'entity reference' => [[['field_test_ref2']], 'field_test_ref2'],
      'entity reference with multi target bundles' => [[['field_test_ref1']], 'field_test_ref1'],
      'entity reference then another entity reference' => [
         [['field_test_ref1', 'field_test_ref3']],
        'field_test_ref1.field_test_ref3',
      ],
      'entity reference with multiple target bundles, each with different field, but the same public field name' => [
        [
          ['field_test_ref1', 'field_test_alias_a'],
          ['field_test_ref1', 'field_test_alias_b'],
        ],
        'field_test_ref1.field_test_alias',
      ],
    ];
  }

  /**
   * Expects an error when an invalid field is provided for include.
   *
   * @param string $entity_type
   *   The entity type for which to test field resolution.
   * @param string $bundle
   *   The entity bundle for which to test field resolution.
   * @param string $external_path
   *   The external field path to resolve.
   * @param string $expected_message
   *   (optional) An expected exception message.
   *
   * @covers ::resolveInternalIncludePath
   * @dataProvider resolveInternalIncludePathErrorProvider
   */
  public function testResolveInternalIncludePathError($entity_type, $bundle, $external_path, $expected_message = '') {
    $path_parts = explode('.', $external_path);
    $this->expectException(CacheableBadRequestHttpException::class);
    if (!empty($expected_message)) {
      $this->expectExceptionMessage($expected_message);
    }
    $resource_type = $this->resourceTypeRepository->get($entity_type, $bundle);
    $this->sut->resolveInternalIncludePath($resource_type, $path_parts);
  }

  /**
   * Provides test cases for ::testResolveInternalIncludePathError.
   */
  public function resolveInternalIncludePathErrorProvider() {
    return [
      // Should fail because none of these bundles have these fields.
      ['entity_test_with_bundle', 'bundle1', 'host.fail!!.deep'],
      ['entity_test_with_bundle', 'bundle2', 'field_test_ref2'],
      ['entity_test_with_bundle', 'bundle1', 'field_test_ref3'],
      // Should fail because the nested fields don't exist on the targeted
      // resource types.
      ['entity_test_with_bundle', 'bundle1', 'field_test_ref1.field_test1'],
      ['entity_test_with_bundle', 'bundle1', 'field_test_ref1.field_test2'],
      ['entity_test_with_bundle', 'bundle1', 'field_test_ref1.field_test_ref1'],
      ['entity_test_with_bundle', 'bundle1', 'field_test_ref1.field_test_ref2'],
      // Should fail because the nested fields is not a valid relationship
      // field name.
      [
        'entity_test_with_bundle', 'bundle1', 'field_test1',
        '`field_test1` is not a valid relationship field name.',
      ],
      // Should fail because the nested fields is not a valid include path.
      [
        'entity_test_with_bundle', 'bundle1', 'field_test_ref1.field_test3',
        '`field_test_ref1.field_test3` is not a valid include path.',
      ],
    ];
  }

  /**
   * @covers ::resolveInternalEntityQueryPath
   * @dataProvider resolveInternalEntityQueryPathProvider
   */
  public function testResolveInternalEntityQueryPath($expect, $external_path, $entity_type_id = 'entity_test_with_bundle', $bundle = 'bundle1') {
    $resource_type = $this->resourceTypeRepository->get($entity_type_id, $bundle);
    $this->assertEquals($expect, $this->sut->resolveInternalEntityQueryPath($resource_type, $external_path));
  }

  /**
   * Provides test cases for ::testResolveInternalEntityQueryPath.
   */
  public function resolveInternalEntityQueryPathProvider() {
    return [
      'config entity as base' => [
        'uuid', 'id', 'entity_test_bundle', 'entity_test_bundle',
      ],
      'config entity as target' => ['type.entity:entity_test_bundle.uuid', 'type.id'],

      'primitive field; variation A' => ['field_test1', 'field_test1'],
      'primitive field; variation B' => ['field_test2', 'field_test2'],

      'entity reference then a primitive field; variation A' => ['field_test_ref2.entity:entity_test_with_bundle.field_test1', 'field_test_ref2.field_test1'],
      'entity reference then a primitive field; variation B' => ['field_test_ref2.entity:entity_test_with_bundle.field_test2', 'field_test_ref2.field_test2'],

      'entity reference then a complex field with property specifier `value`' => ['field_test_ref2.entity:entity_test_with_bundle.field_test_text.value', 'field_test_ref2.field_test_text.value'],
      'entity reference then a complex field with property specifier `format`' => ['field_test_ref2.entity:entity_test_with_bundle.field_test_text.format', 'field_test_ref2.field_test_text.format'],

      'entity reference then no delta with property specifier `id`' => ['field_test_ref1.entity:entity_test_with_bundle.uuid', 'field_test_ref1.id'],
      'entity reference then delta 0 with property specifier `id`' => ['field_test_ref1.0.entity:entity_test_with_bundle.uuid', 'field_test_ref1.0.id'],
      'entity reference then delta 1 with property specifier `id`' => ['field_test_ref1.1.entity:entity_test_with_bundle.uuid', 'field_test_ref1.1.id'],

      'entity reference then no reference property and a complex field with property specifier `value`' => ['field_test_ref1.entity:entity_test_with_bundle.field_test_text.value', 'field_test_ref1.field_test_text.value'],
      'entity reference then a reference property and a complex field with property specifier `value`' => ['field_test_ref1.entity.field_test_text.value', 'field_test_ref1.entity.field_test_text.value'],
      'entity reference then no reference property and a complex field with property specifier `format`' => ['field_test_ref1.entity:entity_test_with_bundle.field_test_text.format', 'field_test_ref1.field_test_text.format'],
      'entity reference then a reference property and a complex field with property specifier `format`' => ['field_test_ref1.entity.field_test_text.format', 'field_test_ref1.entity.field_test_text.format'],

      'entity reference then property specifier `entity:entity_test_with_bundle` then a complex field with property specifier `value`' => ['field_test_ref1.entity:entity_test_with_bundle.field_test_text.value', 'field_test_ref1.entity:entity_test_with_bundle.field_test_text.value'],

      'entity reference with a delta and no reference property then a complex field and property specifier `value`' => ['field_test_ref1.0.entity:entity_test_with_bundle.field_test_text.value', 'field_test_ref1.0.field_test_text.value'],
      'entity reference with a delta and a reference property then a complex field and property specifier `value`' => ['field_test_ref1.0.entity.field_test_text.value', 'field_test_ref1.0.entity.field_test_text.value'],

      'entity reference with no reference property then another entity reference with no reference property a complex field with property specifier `value`' => ['field_test_ref1.entity:entity_test_with_bundle.field_test_ref3.entity:entity_test_with_bundle.field_test_text.value', 'field_test_ref1.field_test_ref3.field_test_text.value'],
      'entity reference with a reference property then another entity reference with no reference property a complex field with property specifier `value`' => ['field_test_ref1.entity.field_test_ref3.entity:entity_test_with_bundle.field_test_text.value', 'field_test_ref1.entity.field_test_ref3.field_test_text.value'],
      'entity reference with no reference property then another entity reference with a reference property a complex field with property specifier `value`' => ['field_test_ref1.entity:entity_test_with_bundle.field_test_ref3.entity.field_test_text.value', 'field_test_ref1.field_test_ref3.entity.field_test_text.value'],
      'entity reference with a reference property then another entity reference with a reference property a complex field with property specifier `value`' => ['field_test_ref1.entity.field_test_ref3.entity.field_test_text.value', 'field_test_ref1.entity.field_test_ref3.entity.field_test_text.value'],

      'entity reference with target bundles then property specifier `entity:entity_test_with_bundle` then a primitive field on multiple bundles' => [
        'field_test_ref1.entity:entity_test_with_bundle.field_test3',
        'field_test_ref1.entity:entity_test_with_bundle.field_test3',
      ],
      'entity reference without target bundles then property specifier `entity:entity_test_with_bundle` then a primitive field on a single bundle' => [
        'field_test_ref2.entity:entity_test_with_bundle.field_test1',
        'field_test_ref2.entity:entity_test_with_bundle.field_test1',
      ],
      'entity reference without target bundles then property specifier `entity:entity_test_with_bundle` then a primitive field on multiple bundles' => [
        'field_test_ref3.entity:entity_test_with_bundle.field_test3',
        'field_test_ref3.entity:entity_test_with_bundle.field_test3',
        'entity_test_with_bundle', 'bundle2',
      ],
      'entity reference without target bundles then property specifier `entity:entity_test_with_bundle` then a primitive field on a single bundle starting from a different resource type' => [
        'field_test_ref3.entity:entity_test_with_bundle.field_test2',
        'field_test_ref3.entity:entity_test_with_bundle.field_test2',
        'entity_test_with_bundle', 'bundle3',
      ],

      'entity reference then property specifier `entity:entity_test_with_bundle` then another entity reference before a primitive field' => ['field_test_ref1.entity:entity_test_with_bundle.field_test_ref3.entity:entity_test_with_bundle.field_test2', 'field_test_ref1.entity:entity_test_with_bundle.field_test_ref3.field_test2'],
    ];
  }

  /**
   * Expects an error when an invalid field is provided for filter and sort.
   *
   * @param string $entity_type
   *   The entity type for which to test field resolution.
   * @param string $bundle
   *   The entity bundle for which to test field resolution.
   * @param string $external_path
   *   The external field path to resolve.
   * @param string $expected_message
   *   (optional) An expected exception message.
   *
   * @covers ::resolveInternalEntityQueryPath
   * @dataProvider resolveInternalEntityQueryPathErrorProvider
   */
  public function testResolveInternalEntityQueryPathError($entity_type, $bundle, $external_path, $expected_message = '') {
    $this->expectException(CacheableBadRequestHttpException::class);
    if (!empty($expected_message)) {
      $this->expectExceptionMessage($expected_message);
    }
    $resource_type = $this->resourceTypeRepository->get($entity_type, $bundle);
    $this->sut->resolveInternalEntityQueryPath($resource_type, $external_path);
  }

  /**
   * Provides test cases for ::testResolveInternalEntityQueryPathError.
   */
  public function resolveInternalEntityQueryPathErrorProvider() {
    return [
      'nested fields' => [
        'entity_test_with_bundle', 'bundle1', 'none.of.these.exist',
      ],
      'field does not exist on bundle' => [
        'entity_test_with_bundle', 'bundle2', 'field_test_ref2',
      ],
      'field does not exist on different bundle' => [
        'entity_test_with_bundle', 'bundle1', 'field_test_ref3',
      ],
      'field does not exist on targeted bundle' => [
        'entity_test_with_bundle', 'bundle1', 'field_test_ref1.field_test1',
      ],
      'different field does not exist on same targeted bundle' => [
        'entity_test_with_bundle', 'bundle1', 'field_test_ref1.field_test2',
      ],
      'entity reference field does not exist on targeted bundle' => [
        'entity_test_with_bundle', 'bundle1', 'field_test_ref1.field_test_ref1',
      ],
      'different entity reference field does not exist on same targeted bundle' => [
        'entity_test_with_bundle', 'bundle1', 'field_test_ref1.field_test_ref2',
      ],
      'message correctly identifies missing field' => [
        'entity_test_with_bundle', 'bundle1',
        'field_test_ref1.entity:entity_test_with_bundle.field_test1',
        'Invalid nested filtering. The field `field_test1`, given in the path `field_test_ref1.entity:entity_test_with_bundle.field_test1`, does not exist.',
      ],
      'message correctly identifies different missing field' => [
        'entity_test_with_bundle', 'bundle1',
        'field_test_ref1.entity:entity_test_with_bundle.field_test2',
        'Invalid nested filtering. The field `field_test2`, given in the path `field_test_ref1.entity:entity_test_with_bundle.field_test2`, does not exist.',
      ],
      'message correctly identifies missing entity reference field' => [
        'entity_test_with_bundle', 'bundle2',
        'field_test_ref1.entity:entity_test_with_bundle.field_test2',
        'Invalid nested filtering. The field `field_test_ref1`, given in the path `field_test_ref1.entity:entity_test_with_bundle.field_test2`, does not exist.',
      ],

      'entity reference then a complex field with no property specifier' => [
        'entity_test_with_bundle', 'bundle1',
        'field_test_ref2.field_test_text',
        'Invalid nested filtering. The field `field_test_text`, given in the path `field_test_ref2.field_test_text` is incomplete, it must end with one of the following specifiers: `value`, `format`, `processed`.',
      ],

      'entity reference then no delta with property specifier `target_id`' => [
        'entity_test_with_bundle', 'bundle1',
        'field_test_ref1.target_id',
        'Invalid nested filtering. The field `target_id`, given in the path `field_test_ref1.target_id`, does not exist.',
      ],
      'entity reference then delta 0 with property specifier `target_id`' => [
        'entity_test_with_bundle', 'bundle1',
        'field_test_ref1.0.target_id',
        'Invalid nested filtering. The field `target_id`, given in the path `field_test_ref1.0.target_id`, does not exist.',
      ],
      'entity reference then delta 1 with property specifier `target_id`' => [
        'entity_test_with_bundle', 'bundle1',
        'field_test_ref1.1.target_id',
        'Invalid nested filtering. The field `target_id`, given in the path `field_test_ref1.1.target_id`, does not exist.',
      ],

      'entity reference then no reference property then a complex field' => [
        'entity_test_with_bundle', 'bundle1',
        'field_test_ref1.field_test_text',
        'Invalid nested filtering. The field `field_test_text`, given in the path `field_test_ref1.field_test_text` is incomplete, it must end with one of the following specifiers: `value`, `format`, `processed`.',

      ],
      'entity reference then reference property then a complex field' => [
        'entity_test_with_bundle', 'bundle1',
        'field_test_ref1.entity.field_test_text',
        'Invalid nested filtering. The field `field_test_text`, given in the path `field_test_ref1.entity.field_test_text` is incomplete, it must end with one of the following specifiers: `value`, `format`, `processed`.',
      ],

      'entity reference then property specifier `entity:entity_test_with_bundle` then a complex field' => [
        'entity_test_with_bundle', 'bundle1',
        'field_test_ref1.entity:entity_test_with_bundle.field_test_text',
        'Invalid nested filtering. The field `field_test_text`, given in the path `field_test_ref1.entity:entity_test_with_bundle.field_test_text` is incomplete, it must end with one of the following specifiers: `value`, `format`, `processed`.',
      ],
    ];
  }

  /**
   * Create a simple bundle.
   *
   * @param string $name
   *   The name of the bundle to create.
   */
  protected function makeBundle($name) {
    EntityTestBundle::create([
      'id' => $name,
    ])->save();
  }

  /**
   * Creates a field for a specified entity type/bundle.
   *
   * @param string $type
   *   The field type.
   * @param string $name
   *   The name of the field to create.
   * @param string $entity_type
   *   The entity type to which the field will be attached.
   * @param string[] $bundles
   *   The entity bundles to which the field will be attached.
   * @param array $storage_settings
   *   Custom storage settings for the field.
   * @param array $config_settings
   *   Custom configuration settings for the field.
   */
  protected function makeField($type, $name, $entity_type, array $bundles, array $storage_settings = [], array $config_settings = []) {
    $storage_config = [
      'field_name' => $name,
      'type' => $type,
      'entity_type' => $entity_type,
      'settings' => $storage_settings,
    ];

    FieldStorageConfig::create($storage_config)->save();

    foreach ($bundles as $bundle) {
      FieldConfig::create([
        'field_name' => $name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'settings' => $config_settings,
      ])->save();
    }
  }

}
