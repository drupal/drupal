<?php

namespace Drupal\Tests\jsonapi\Kernel\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifier;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\JsonApiResource\Data;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @coversDefaultClass \Drupal\jsonapi\Controller\EntityResource
 * @group jsonapi
 *
 * @internal
 */
class EntityResourceTest extends JsonapiKernelTestBase {

  /**
   * Static UUIDs to use in testing.
   *
   * @var array
   */
  protected static $nodeUuid = [
    1 => '83bc47ad-2c58-45e3-9136-abcdef111111',
    2 => '83bc47ad-2c58-45e3-9136-abcdef222222',
    3 => '83bc47ad-2c58-45e3-9136-abcdef333333',
    4 => '83bc47ad-2c58-45e3-9136-abcdef444444',
  ];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field',
    'jsonapi',
    'serialization',
    'system',
    'user',
  ];

  /**
   * The user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * The other node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node2;

  /**
   * An unpublished node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node3;

  /**
   * A fake request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The EntityResource under test.
   *
   * @var \Drupal\jsonapi\Controller\EntityResource
   */
  protected $entityResource;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add the entity schemas.
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    // Add the additional table schemas.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);
    NodeType::create([
      'type' => 'lorem',
    ])->save();
    $type = NodeType::create([
      'type' => 'article',
    ]);
    $type->save();
    $this->user = User::create([
      'name' => 'user1',
      'mail' => 'user@localhost',
      'status' => 1,
      'roles' => ['test_role_one', 'test_role_two'],
    ]);
    $this->createEntityReferenceField('node', 'article', 'field_relationships', 'Relationship', 'node', 'default', ['target_bundles' => ['article']], FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $this->user->save();

    $this->node = Node::create([
      'title' => 'dummy_title',
      'type' => 'article',
      'uid' => $this->user->id(),
      'uuid' => static::$nodeUuid[1],
    ]);
    $this->node->save();

    $this->node2 = Node::create([
      'type' => 'article',
      'title' => 'Another test node',
      'uid' => $this->user->id(),
      'uuid' => static::$nodeUuid[2],
    ]);
    $this->node2->save();

    $this->node3 = Node::create([
      'type' => 'article',
      'title' => 'Unpublished test node',
      'uid' => $this->user->id(),
      'status' => 0,
      'uuid' => static::$nodeUuid[3],
    ]);
    $this->node3->save();

    $this->node4 = Node::create([
      'type' => 'article',
      'title' => 'Test node with related nodes',
      'uid' => $this->user->id(),
      'field_relationships' => [
        ['target_id' => $this->node->id()],
        ['target_id' => $this->node2->id()],
        ['target_id' => $this->node3->id()],
      ],
      'uuid' => static::$nodeUuid[4],
    ]);
    $this->node4->save();

    // Give anonymous users permission to view user profiles, so that we can
    // verify the cache tags of cached versions of user profile pages.
    array_map(function ($role_id) {
      Role::create([
        'id' => $role_id,
        'permissions' => [
          'access user profiles',
          'access content',
        ],
      ])->save();
    }, [RoleInterface::ANONYMOUS_ID, 'test_role_one', 'test_role_two']);

    $this->entityResource = $this->createEntityResource();
  }

  /**
   * Creates an instance of the subject under test.
   *
   * @return \Drupal\jsonapi\Controller\EntityResource
   *   An EntityResource instance.
   */
  protected function createEntityResource() {
    return $this->container->get('jsonapi.entity_resource');
  }

  /**
   * @covers ::getIndividual
   */
  public function testGetIndividual() {
    $response = $this->entityResource->getIndividual($this->node, Request::create('/jsonapi/node/article'));
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response->getResponseData());
    $resource_object = $response->getResponseData()->getData()->getIterator()->offsetGet(0);
    $this->assertEquals($this->node->uuid(), $resource_object->getId());
  }

  /**
   * @covers ::getIndividual
   */
  public function testGetIndividualDenied() {
    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $role->revokePermission('access content');
    $role->save();
    $this->expectException(EntityAccessDeniedHttpException::class);
    $this->entityResource->getIndividual($this->node, Request::create('/jsonapi/node/article'));
  }

  /**
   * @covers ::getCollection
   */
  public function testGetCollection() {
    $request = Request::create('/jsonapi/node/article');
    $request->query = new ParameterBag(['sort' => 'nid']);

    // Get the response.
    $resource_type = new ResourceType('node', 'article', NULL);
    $response = $this->entityResource->getCollection($resource_type, $request);

    // Assertions.
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response->getResponseData());
    $this->assertInstanceOf(Data::class, $response->getResponseData()->getData());
    $this->assertEquals($this->node->uuid(), $response->getResponseData()->getData()->getIterator()->current()->getId());
    $this->assertEquals([
      'node:1',
      'node:2',
      'node:3',
      'node:4',
      'node_list',
    ], $response->getCacheableMetadata()->getCacheTags());
  }

  /**
   * @covers ::getCollection
   */
  public function testGetFilteredCollection() {
    $request = Request::create('/jsonapi/node/article');
    $request->query = new ParameterBag(['filter' => ['type' => 'article']]);

    $entity_resource = $this->createEntityResource();

    // Get the response.
    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('node_type', 'node_type');
    $response = $entity_resource->getCollection($resource_type, $request);

    // Assertions.
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response->getResponseData());
    $this->assertInstanceOf(Data::class, $response->getResponseData()->getData());
    $this->assertCount(1, $response->getResponseData()->getData());
    $expected_cache_tags = [
      'config:node.type.article',
      'config:node_type_list',
    ];
    $this->assertSame($expected_cache_tags, $response->getCacheableMetadata()->getCacheTags());
  }

  /**
   * @covers ::getCollection
   */
  public function testGetSortedCollection() {
    $request = Request::create('/jsonapi/node/article');
    $request->query = new ParameterBag(['sort' => '-type']);

    $entity_resource = $this->createEntityResource();

    // Get the response.
    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('node_type', 'node_type');
    $response = $entity_resource->getCollection($resource_type, $request);

    // Assertions.
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response->getResponseData());
    $this->assertInstanceOf(Data::class, $response->getResponseData()->getData());
    $this->assertCount(2, $response->getResponseData()->getData());
    // `drupal_internal__type` is the alias for a node_type entity's ID field.
    $this->assertEquals($response->getResponseData()->getData()->toArray()[0]->getField('drupal_internal__type'), 'lorem');
    $expected_cache_tags = [
      'config:node.type.article',
      'config:node.type.lorem',
      'config:node_type_list',
    ];
    $this->assertSame($expected_cache_tags, $response->getCacheableMetadata()->getCacheTags());
  }

  /**
   * @covers ::getCollection
   */
  public function testGetPagedCollection() {
    $request = Request::create('/jsonapi/node/article');
    $request->query = new ParameterBag([
      'sort' => 'nid',
      'page' => [
        'offset' => 1,
        'limit' => 1,
      ],
    ]);

    $entity_resource = $this->createEntityResource();

    // Get the response.
    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('node', 'article');
    $response = $entity_resource->getCollection($resource_type, $request);

    // Assertions.
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response->getResponseData());
    $this->assertInstanceOf(Data::class, $response->getResponseData()->getData());
    $data = $response->getResponseData()->getData();
    $this->assertCount(1, $data);
    $this->assertEquals($this->node2->uuid(), $data->toArray()[0]->getId());
    $this->assertEquals(['node:2', 'node_list'], $response->getCacheableMetadata()->getCacheTags());
  }

  /**
   * @covers ::getCollection
   */
  public function testGetEmptyCollection() {
    $request = Request::create('/jsonapi/node/article');
    $request->query = new ParameterBag(['filter' => ['id' => 'invalid']]);

    // Get the response.
    $resource_type = new ResourceType('node', 'article', NULL);
    $response = $this->entityResource->getCollection($resource_type, $request);

    // Assertions.
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response->getResponseData());
    $this->assertInstanceOf(Data::class, $response->getResponseData()->getData());
    $this->assertEquals(0, $response->getResponseData()->getData()->count());
    $this->assertEquals(['node_list'], $response->getCacheableMetadata()->getCacheTags());
  }

  /**
   * @covers ::getRelated
   */
  public function testGetRelated() {
    // to-one relationship.
    $resource_type = new ResourceType('node', 'article', NULL);
    $resource_type->setRelatableResourceTypes([
      'uid' => [new ResourceType('user', 'user', NULL)],
      'roles' => [new ResourceType('user_role', 'user_role', NULL)],
      'field_relationships' => [new ResourceType('node', 'article', NULL)],
    ]);
    $response = $this->entityResource->getRelated($resource_type, $this->node, 'uid', Request::create('/jsonapi/node/article/' . $this->node->uuid(), '/uid'));
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response->getResponseData());
    $this->assertInstanceOf(ResourceObject::class, $response->getResponseData()->getData()->toArray()[0]);
    $this->assertEquals($this->user->uuid(), $response->getResponseData()->getData()->toArray()[0]->getId());
    $this->assertEquals(['node:1'], $response->getCacheableMetadata()->getCacheTags());
    // to-many relationship.
    $response = $this->entityResource->getRelated($resource_type, $this->node4, 'field_relationships', Request::create('/jsonapi/node/article/' . $this->node4->uuid(), '/field_relationships'));
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response
      ->getResponseData());
    $this->assertInstanceOf(Data::class, $response
      ->getResponseData()
      ->getData());
    $this->assertEquals(['node:4'], $response->getCacheableMetadata()->getCacheTags());
  }

  /**
   * @covers ::getRelationship
   */
  public function testGetRelationship() {
    // to-one relationship.
    $resource_type = new ResourceType('node', 'article', NULL);
    $resource_type->setRelatableResourceTypes([
      'uid' => [new ResourceType('user', 'user', NULL)],
    ]);
    $response = $this->entityResource->getRelationship($resource_type, $this->node, 'uid', Request::create('/jsonapi/node/article/' . $this->node->uuid() . '/relationships/uid'));
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response->getResponseData());
    $this->assertInstanceOf(
      EntityReferenceFieldItemListInterface::class,
      $response->getResponseData()->getData()
    );
    $this->assertEquals(1, $response
      ->getResponseData()
      ->getData()
      ->getEntity()
      ->id()
    );
    $this->assertEquals('node', $response
      ->getResponseData()
      ->getData()
      ->getEntity()
      ->getEntityTypeId()
    );
  }

  /**
   * @covers ::createIndividual
   */
  public function testCreateIndividual() {
    Role::load(Role::ANONYMOUS_ID)
      ->grantPermission('create article content')
      ->save();
    $content = Json::encode([
      'data' => [
        'type' => 'node--article',
        'attributes' => [
          'title' => 'Lorem ipsum',
        ],
      ],
    ]);
    $request = Request::create('/jsonapi/node/article', 'POST', [], [], [], [], $content);
    $resource_type = new ResourceType('node', 'article', Node::class);
    $resource_type->setRelatableResourceTypes([
      'field_relationships' => [new ResourceType('node', 'article', NULL)],
    ]);
    $response = $this->entityResource->createIndividual($resource_type, $request);
    // As a side effect, the node will also be saved.
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response->getResponseData());
    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->assertTrue($entity_type_manager->getStorage('node')->loadByProperties(['uuid' => $response->getResponseData()->getData()->getIterator()->offsetGet(0)->getId()]));
    $this->assertEquals(201, $response->getStatusCode());
  }

  /**
   * @covers ::createIndividual
   */
  public function testCreateIndividualWithMissingRequiredData() {
    Role::load(Role::ANONYMOUS_ID)
      ->grantPermission('create article content')
      ->save();
    $this->expectException(HttpException::class);
    $this->expectExceptionMessage('Unprocessable Entity: validation failed.');
    $resource_type = new ResourceType('node', 'article', Node::class);
    $payload = Json::encode([
      'data' => [
        'type' => 'article',
      ],
    ]);
    $this->entityResource->createIndividual($resource_type, Request::create('/jsonapi/node/article', 'POST', [], [], [], [], $payload));
  }

  /**
   * @covers ::createIndividual
   */
  public function testCreateIndividualDuplicateError() {
    Role::load(Role::ANONYMOUS_ID)
      ->grantPermission('create article content')
      ->save();

    $node = Node::create([
      'type' => 'article',
      'title' => 'Lorem ipsum',
    ]);
    $node->save();
    $node->enforceIsNew();

    $payload = Json::encode([
      'data' => [
        'type' => 'article',
        'id' => $this->node->uuid(),
        'attributes' => [
          'title' => 'foobar',
        ],
      ],
    ]);

    $this->expectException(ConflictHttpException::class);
    $this->expectExceptionMessage('Conflict: Entity already exists.');
    $resource_type = new ResourceType('node', 'article', Node::class);
    $resource_type->setRelatableResourceTypes([
      'field_relationships' => [new ResourceType('node', 'article', NULL)],
    ]);
    $this->entityResource->createIndividual($resource_type, Request::create('/jsonapi/node/article', 'POST', [], [], [], [], $payload));
  }

  /**
   * @covers ::patchIndividual
   */
  public function testPatchIndividual() {
    Role::load(Role::ANONYMOUS_ID)
      ->grantPermission('edit any article content')
      ->save();
    $payload = Json::encode([
      'data' => [
        'type' => 'article',
        'id' => $this->node->uuid(),
        'attributes' => [
          'title' => 'PATCHED',
        ],
        'relationships' => [
          'field_relationships' => [
            'data' => [
              'id' => Node::load(1)->uuid(),
              'type' => 'node--article',
            ],
          ],
        ],
      ],
    ]);
    $request = Request::create('/jsonapi/node/article/' . $this->node->uuid(), 'PATCH', [], [], [], [], $payload);

    // Create a new EntityResource that uses uuid.
    $resource_type = new ResourceType('node', 'article', Node::class);
    $resource_type->setRelatableResourceTypes([
      'field_relationships' => [new ResourceType('node', 'article', NULL)],
    ]);
    $response = $this->entityResource->patchIndividual($resource_type, $this->node, $request);

    // As a side effect, the node will also be saved.
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response->getResponseData());
    $updated_node = $response->getResponseData()->getData()->getIterator()->offsetGet(0);
    $this->assertInstanceOf(ResourceObject::class, $updated_node);
    $this->assertSame('PATCHED', $this->node->getTitle());
    $this->assertSame([['target_id' => '1']], $this->node->get('field_relationships')->getValue());
    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * @covers ::deleteIndividual
   */
  public function testDeleteIndividual() {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Lorem ipsum',
    ]);
    $nid = $node->id();
    $node->save();
    Role::load(Role::ANONYMOUS_ID)
      ->grantPermission('delete own article content')
      ->save();
    $response = $this->entityResource->deleteIndividual($node);
    // As a side effect, the node will also be deleted.
    $count = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->getQuery()
      ->condition('nid', $nid)
      ->count()
      ->execute();
    $this->assertEquals(0, $count);
    $this->assertNull($response->getResponseData());
    $this->assertEquals(204, $response->getStatusCode());
  }

  /**
   * @covers ::addToRelationshipData
   */
  public function testAddToRelationshipData() {
    Role::load(Role::ANONYMOUS_ID)
      ->grantPermission('edit any article content')
      ->save();

    $resource_type = new ResourceType('node', 'article', NULL);
    $resource_type->setRelatableResourceTypes([
      'field_relationships' => [new ResourceType('node', 'article', NULL)],
    ]);
    $payload = Json::encode([
      'data' => [
        [
          'type' => 'node--article',
          'id' => $this->node->uuid(),
        ],
      ],
    ]);
    $request = Request::create('/jsonapi/node/article/' . $this->node->uuid() . '/relationships/field_relationships', 'POST', [], [], [], [], $payload);
    $response = $this->entityResource->addToRelationshipData($resource_type, $this->node, 'field_relationships', $request);

    // As a side effect, the node will also be saved.
    $this->assertNotEmpty($this->node->id());
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response->getResponseData());
    $field_list = $response->getResponseData()->getData();
    $this->assertInstanceOf(EntityReferenceFieldItemListInterface::class, $field_list);
    $this->assertSame('field_relationships', $field_list->getName());
    $this->assertEquals([['target_id' => 1]], $field_list->getValue());
    $this->assertEquals(204, $response->getStatusCode());
  }

  /**
   * @covers ::replaceRelationshipData
   * @dataProvider replaceRelationshipDataProvider
   */
  public function testReplaceRelationshipData($relationships) {
    $this->node->field_relationships->appendItem(['target_id' => $this->node->id()]);
    $this->node->save();
    Role::load(Role::ANONYMOUS_ID)
      ->grantPermission('edit any article content')
      ->save();

    $resource_type = new ResourceType('node', 'article', NULL);
    $resource_type->setRelatableResourceTypes([
      'field_relationships' => [new ResourceType('node', 'article', NULL)],
    ]);
    $payload = ['data' => []];
    foreach ($relationships as $relationship) {
      $payload['data'][] = [
        'type' => $relationship->getTypeName(),
        'id' => $relationship->getId(),
      ];
    }
    $request = Request::create('/jsonapi/node/article/' . $this->node->uuid() . '/relationships/field_relationships', 'PATCH', [], [], [], [], Json::encode($payload));
    $response = $this->entityResource->replaceRelationshipData($resource_type, $this->node, 'field_relationships', $request);

    // As a side effect, the node will also be saved.
    $this->assertNotEmpty($this->node->id());
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response->getResponseData());
    $field_list = $response->getResponseData()->getData();
    $this->assertInstanceOf(EntityReferenceFieldItemListInterface::class, $field_list);
    $this->assertSame('field_relationships', $field_list->getName());
    $this->assertEquals(
      array_map(function (ResourceIdentifier $identifier) {
        return $identifier->getId();
      }, $relationships),
      array_map(function (EntityInterface $entity) {
        return $entity->uuid();
      }, $field_list->referencedEntities())
    );
    $this->assertEquals(204, $response->getStatusCode());
  }

  /**
   * Provides data for the testPatchRelationship.
   *
   * @return array
   *   The input data for the test function.
   */
  public function replaceRelationshipDataProvider() {
    return [
      // Replace relationships.
      [
        [
          new ResourceIdentifier('node--article', static::$nodeUuid[1]),
          new ResourceIdentifier('node--article', static::$nodeUuid[2]),
        ],
      ],
      // Remove relationships.
      [[]],
    ];
  }

  /**
   * @covers ::removeFromRelationshipData
   * @dataProvider removeFromRelationshipDataProvider
   */
  public function testRemoveFromRelationshipData($deleted_rels, $kept_rels) {
    $this->node->field_relationships->appendItem(['target_id' => $this->node->id()]);
    $this->node->field_relationships->appendItem(['target_id' => $this->node2->id()]);
    $this->node->save();
    Role::load(Role::ANONYMOUS_ID)
      ->grantPermission('edit any article content')
      ->save();

    $resource_type = new ResourceType('node', 'article', NULL);
    $resource_type->setRelatableResourceTypes([
      'field_relationships' => [new ResourceType('node', 'article', NULL)],
    ]);
    $payload = ['data' => []];
    foreach ($deleted_rels as $deleted_rel) {
      $payload['data'][] = [
        'type' => $deleted_rel->getTypeName(),
        'id' => $deleted_rel->getId(),
      ];
    }
    $request = Request::create('/jsonapi/node/article/' . $this->node->uuid() . '/relationships/field_relationships', 'DELETE', [], [], [], [], Json::encode($payload));
    $response = $this->entityResource->removeFromRelationshipData($resource_type, $this->node, 'field_relationships', $request);

    // As a side effect, the node will also be saved.
    $this->assertInstanceOf(JsonApiDocumentTopLevel::class, $response->getResponseData());
    $field_list = $response->getResponseData()->getData();
    $this->assertInstanceOf(EntityReferenceFieldItemListInterface::class, $field_list);
    $this->assertSame('field_relationships', $field_list->getName());
    $this->assertEquals($kept_rels, $field_list->getValue());
    $this->assertEquals(204, $response->getStatusCode());
  }

  /**
   * Provides data for the testDeleteRelationship.
   *
   * @return array
   *   The input data for the test function.
   */
  public function removeFromRelationshipDataProvider() {
    return [
      // Remove one relationship.
      [
        [
          new ResourceIdentifier('node--article', static::$nodeUuid[1]),
        ],
        [['target_id' => 2]],
      ],
      // Remove all relationships.
      [
        [
          new ResourceIdentifier('node--article', static::$nodeUuid[2]),
          new ResourceIdentifier('node--article', static::$nodeUuid[1]),
        ],
        [],
      ],
      // Remove no relationship.
      [[], [['target_id' => 1], ['target_id' => 2]]],
    ];
  }

}
