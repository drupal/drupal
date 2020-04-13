<?php

namespace Drupal\Tests\jsonapi\Kernel\Controller;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
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
  protected static $modules = [
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
  protected function setUp(): void {
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

}
