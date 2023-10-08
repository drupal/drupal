<?php

namespace Drupal\Tests\jsonapi\Kernel\ResourceType;

use Drupal\Core\Cache\Cache;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;

/**
 * @coversDefaultClass \Drupal\jsonapi\ResourceType\ResourceTypeRepository
 * @group jsonapi
 *
 * @internal
 */
class ResourceTypeRepositoryTest extends JsonapiKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'field',
    'node',
    'serialization',
    'system',
    'user',
    'jsonapi_test_resource_type_building',
  ];

  /**
   * The JSON:API resource type repository under test.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Add the entity schemas.
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    // Add the additional table schemas.
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();
    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();
    NodeType::create([
      'type' => '42',
      'name' => '42',
    ])->save();

    $this->resourceTypeRepository = $this->container->get('jsonapi.resource_type.repository');
  }

  /**
   * @covers ::all
   */
  public function testAll() {
    // Make sure that there are resources being created.
    $all = $this->resourceTypeRepository->all();
    $this->assertNotEmpty($all);
    array_walk($all, function (ResourceType $resource_type) {
      $this->assertNotEmpty($resource_type->getDeserializationTargetClass());
      $this->assertNotEmpty($resource_type->getEntityTypeId());
      $this->assertNotEmpty($resource_type->getTypeName());
    });
  }

  /**
   * @covers ::get
   * @dataProvider getProvider
   */
  public function testGet($entity_type_id, $bundle, $entity_class) {
    // Make sure that there are resources being created.
    $resource_type = $this->resourceTypeRepository->get($entity_type_id, $bundle);
    $this->assertInstanceOf(ResourceType::class, $resource_type);
    $this->assertSame($entity_class, $resource_type->getDeserializationTargetClass());
    $this->assertSame($entity_type_id, $resource_type->getEntityTypeId());
    $this->assertSame($bundle, $resource_type->getBundle());
    $this->assertSame($entity_type_id . '--' . $bundle, $resource_type->getTypeName());
  }

  /**
   * Data provider for testGet.
   *
   * @return array
   *   The data for the test method.
   */
  public function getProvider() {
    return [
      ['node', 'article', 'Drupal\node\Entity\Node'],
      ['node', '42', 'Drupal\node\Entity\Node'],
      ['node_type', 'node_type', 'Drupal\node\Entity\NodeType'],
      ['menu', 'menu', 'Drupal\system\Entity\Menu'],
    ];
  }

  /**
   * Ensures that the ResourceTypeRepository's cache does not become stale.
   */
  public function testCaching() {
    $this->assertEmpty($this->resourceTypeRepository->get('node', 'article')->getRelatableResourceTypesByField('field_relationship'));
    $this->createEntityReferenceField('node', 'article', 'field_relationship', 'Related entity', 'node');
    $this->assertCount(3, $this->resourceTypeRepository->get('node', 'article')->getRelatableResourceTypesByField('field_relationship'));
    NodeType::create([
      'type' => 'camelids',
      'name' => 'Camelids',
    ])->save();
    $this->assertCount(4, $this->resourceTypeRepository->get('node', 'article')->getRelatableResourceTypesByField('field_relationship'));
  }

  /**
   * Ensures that a naming conflict in mapping causes an exception to be thrown.
   *
   * @covers ::getFields
   * @dataProvider getFieldsProvider
   */
  public function testMappingNameConflictCheck($field_name_list) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition('node');
    $bundle = 'article';
    $reflection_class = new \ReflectionClass($this->resourceTypeRepository);
    $reflection_method = $reflection_class->getMethod('getFields');

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage("The generated alias '{$field_name_list[1]}' for field name '{$field_name_list[0]}' conflicts with an existing field. Report this in the JSON:API issue queue!");
    $reflection_method->invokeArgs($this->resourceTypeRepository, [$field_name_list, $entity_type, $bundle]);
  }

  /**
   * Data provider for testMappingNameConflictCheck.
   *
   * These field name lists are designed to trigger a naming conflict in the
   * mapping: the special-cased names "type" or "id", and the name
   * "{$entity_type_id}_type" or "{$entity_type_id}_id", respectively.
   *
   * @return array
   *   The data for the test method.
   */
  public function getFieldsProvider() {
    return [
      [['type', 'node_type']],
      [['id', 'node_id']],
    ];
  }

  /**
   * Tests that resource types can be disabled by a build subscriber.
   */
  public function testResourceTypeDisabling() {
    $this->assertFalse($this->resourceTypeRepository->getByTypeName('node--article')->isInternal());
    $this->assertFalse($this->resourceTypeRepository->getByTypeName('node--page')->isInternal());
    $this->assertFalse($this->resourceTypeRepository->getByTypeName('user--user')->isInternal());
    $disabled_resource_types = [
      'node--page',
      'user--user',
    ];
    \Drupal::state()->set('jsonapi_test_resource_type_builder.disabled_resource_types', $disabled_resource_types);
    Cache::invalidateTags(['jsonapi_resource_types']);
    $this->assertFalse($this->resourceTypeRepository->getByTypeName('node--article')->isInternal());
    $this->assertTrue($this->resourceTypeRepository->getByTypeName('node--page')->isInternal());
    $this->assertTrue($this->resourceTypeRepository->getByTypeName('user--user')->isInternal());
  }

  /**
   * Tests that resource type fields can be aliased per resource type.
   */
  public function testResourceTypeFieldAliasing() {
    $this->assertSame($this->resourceTypeRepository->getByTypeName('node--article')->getPublicName('uid'), 'uid');
    $this->assertSame($this->resourceTypeRepository->getByTypeName('node--page')->getPublicName('uid'), 'uid');
    $resource_type_field_aliases = [
      'node--article' => [
        'uid' => 'author',
      ],
      'node--page' => [
        'uid' => 'owner',
      ],
    ];
    \Drupal::state()->set('jsonapi_test_resource_type_builder.resource_type_field_aliases', $resource_type_field_aliases);
    Cache::invalidateTags(['jsonapi_resource_types']);
    $this->assertSame($this->resourceTypeRepository->getByTypeName('node--article')->getPublicName('uid'), 'author');
    $this->assertSame($this->resourceTypeRepository->getByTypeName('node--page')->getPublicName('uid'), 'owner');
  }

  /**
   * Tests that resource type fields can be disabled per resource type.
   */
  public function testResourceTypeFieldDisabling() {
    $this->assertTrue($this->resourceTypeRepository->getByTypeName('node--article')->isFieldEnabled('uid'));
    $this->assertTrue($this->resourceTypeRepository->getByTypeName('node--page')->isFieldEnabled('uid'));
    $disabled_resource_type_fields = [
      'node--article' => [
        'uid' => TRUE,
      ],
      'node--page' => [
        'uid' => FALSE,
      ],
    ];
    \Drupal::state()->set('jsonapi_test_resource_type_builder.disabled_resource_type_fields', $disabled_resource_type_fields);
    Cache::invalidateTags(['jsonapi_resource_types']);
    $this->assertFalse($this->resourceTypeRepository->getByTypeName('node--article')->isFieldEnabled('uid'));
    $this->assertTrue($this->resourceTypeRepository->getByTypeName('node--page')->isFieldEnabled('uid'));
  }

  /**
   * Tests that resource types can be renamed.
   */
  public function testResourceTypeRenaming() {
    \Drupal::state()->set('jsonapi_test_resource_type_builder.renamed_resource_types', [
      'node--article' => 'articles',
      'node--page' => 'pages',
    ]);
    Cache::invalidateTags(['jsonapi_resource_types']);
    $this->assertNull($this->resourceTypeRepository->getByTypeName('node--article'));
    $this->assertInstanceOf(ResourceType::class, $this->resourceTypeRepository->getByTypeName('articles'));
    $this->assertNull($this->resourceTypeRepository->getByTypeName('node--page'));
    $this->assertInstanceOf(ResourceType::class, $this->resourceTypeRepository->getByTypeName('pages'));
  }

}
