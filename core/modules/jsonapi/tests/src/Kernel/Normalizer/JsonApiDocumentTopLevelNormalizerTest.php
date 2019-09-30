<?php

namespace Drupal\Tests\jsonapi\Kernel\Normalizer;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\jsonapi\JsonApiResource\ErrorCollection;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer
 * @group jsonapi
 *
 * @internal
 */
class JsonApiDocumentTopLevelNormalizerTest extends JsonapiKernelTestBase {

  use ImageFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'jsonapi',
    'field',
    'node',
    'serialization',
    'system',
    'taxonomy',
    'text',
    'filter',
    'user',
    'file',
    'image',
    'jsonapi_test_normalizers_kernel',
  ];

  /**
   * A node to normalize.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $node;

  /**
   * A user to normalize.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The include resolver.
   *
   * @var \Drupal\jsonapi\IncludeResolver
   */
  protected $includeResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add the entity schemas.
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('file');
    // Add the additional table schemas.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);
    $this->installSchema('file', ['file_usage']);
    $type = NodeType::create([
      'type' => 'article',
    ]);
    $type->save();
    $this->createEntityReferenceField(
      'node',
      'article',
      'field_tags',
      'Tags',
      'taxonomy_term',
      'default',
      ['target_bundles' => ['tags']],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    $this->createTextField('node', 'article', 'body', 'Body');

    $this->createImageField('field_image', 'article');

    $this->user = User::create([
      'name' => 'user1',
      'mail' => 'user@localhost',
    ]);
    $this->user2 = User::create([
      'name' => 'user2',
      'mail' => 'user2@localhost',
    ]);

    $this->user->save();
    $this->user2->save();

    $this->vocabulary = Vocabulary::create(['name' => 'Tags', 'vid' => 'tags']);
    $this->vocabulary->save();

    $this->term1 = Term::create([
      'name' => 'term1',
      'vid' => $this->vocabulary->id(),
    ]);
    $this->term2 = Term::create([
      'name' => 'term2',
      'vid' => $this->vocabulary->id(),
    ]);

    $this->term1->save();
    $this->term2->save();

    $this->file = File::create([
      'uri' => 'public://example.png',
      'filename' => 'example.png',
    ]);
    $this->file->save();

    $this->node = Node::create([
      'title' => 'dummy_title',
      'type' => 'article',
      'uid' => 1,
      'body' => [
        'format' => 'plain_text',
        'value' => $this->randomStringValidate(42),
      ],
      'field_tags' => [
        ['target_id' => $this->term1->id()],
        ['target_id' => $this->term2->id()],
      ],
      'field_image' => [
        [
          'target_id' => $this->file->id(),
          'alt' => 'test alt',
          'title' => 'test title',
          'width' => 10,
          'height' => 11,
        ],
      ],
    ]);

    $this->node->save();

    $this->nodeType = NodeType::load('article');

    Role::create([
      'id' => RoleInterface::ANONYMOUS_ID,
      'permissions' => [
        'access content',
      ],
    ])->save();

    $this->includeResolver = $this->container->get('jsonapi.include_resolver');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    if ($this->node) {
      $this->node->delete();
    }
    if ($this->term1) {
      $this->term1->delete();
    }
    if ($this->term2) {
      $this->term2->delete();
    }
    if ($this->vocabulary) {
      $this->vocabulary->delete();
    }
    if ($this->user) {
      $this->user->delete();
    }
    if ($this->user2) {
      $this->user2->delete();
    }
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    list($request, $resource_type) = $this->generateProphecies('node', 'article');

    $resource_object = ResourceObject::createFromEntity($resource_type, $this->node);
    $includes = $this->includeResolver->resolve($resource_object, 'uid,field_tags,field_image');

    $jsonapi_doc_object = $this
      ->getNormalizer()
      ->normalize(
        new JsonApiDocumentTopLevel(new ResourceObjectData([$resource_object], 1), $includes, new LinkCollection([])),
        'api_json',
        [
          'resource_type' => $resource_type,
          'account' => NULL,
          'sparse_fieldset' => [
            'node--article' => [
              'title',
              'node_type',
              'uid',
              'field_tags',
              'field_image',
            ],
            'user--user' => [
              'display_name',
            ],
          ],
          'include' => [
            'uid',
            'field_tags',
            'field_image',
          ],
        ]
      );
    $normalized = $jsonapi_doc_object->getNormalization();

    // @see http://jsonapi.org/format/#document-jsonapi-object
    $this->assertEquals($normalized['jsonapi']['version'], '1.0');
    $this->assertEquals($normalized['jsonapi']['meta']['links']['self']['href'], 'http://jsonapi.org/format/1.0/');

    $this->assertSame($normalized['data']['attributes']['title'], 'dummy_title');
    $this->assertEquals($normalized['data']['id'], $this->node->uuid());
    $this->assertSame([
      'data' => [
        'type' => 'node_type--node_type',
        'id' => NodeType::load('article')->uuid(),
      ],
      'links' => [
        'related' => ['href' => Url::fromUri('internal:/jsonapi/node/article/' . $this->node->uuid() . '/node_type', ['query' => ['resourceVersion' => 'id:' . $this->node->getRevisionId()]])->setAbsolute()->toString(TRUE)->getGeneratedUrl()],
        'self' => ['href' => Url::fromUri('internal:/jsonapi/node/article/' . $this->node->uuid() . '/relationships/node_type', ['query' => ['resourceVersion' => 'id:' . $this->node->getRevisionId()]])->setAbsolute()->toString(TRUE)->getGeneratedUrl()],
      ],
    ], $normalized['data']['relationships']['node_type']);
    $this->assertTrue(!isset($normalized['data']['attributes']['created']));
    $this->assertEquals([
      'alt' => 'test alt',
      'title' => 'test title',
      'width' => 10,
      'height' => 11,
    ], $normalized['data']['relationships']['field_image']['data']['meta']);
    $this->assertSame('node--article', $normalized['data']['type']);
    $this->assertEquals([
      'data' => [
        'type' => 'user--user',
        'id' => $this->user->uuid(),
      ],
      'links' => [
        'self' => ['href' => Url::fromUri('internal:/jsonapi/node/article/' . $this->node->uuid() . '/relationships/uid', ['query' => ['resourceVersion' => 'id:' . $this->node->getRevisionId()]])->setAbsolute()->toString(TRUE)->getGeneratedUrl()],
        'related' => ['href' => Url::fromUri('internal:/jsonapi/node/article/' . $this->node->uuid() . '/uid', ['query' => ['resourceVersion' => 'id:' . $this->node->getRevisionId()]])->setAbsolute()->toString(TRUE)->getGeneratedUrl()],
      ],
    ], $normalized['data']['relationships']['uid']);
    $this->assertTrue(empty($normalized['meta']['omitted']));
    $this->assertSame($this->user->uuid(), $normalized['included'][0]['id']);
    $this->assertSame('user--user', $normalized['included'][0]['type']);
    $this->assertSame('user1', $normalized['included'][0]['attributes']['display_name']);
    $this->assertCount(1, $normalized['included'][0]['attributes']);
    $this->assertSame($this->term1->uuid(), $normalized['included'][1]['id']);
    $this->assertSame('taxonomy_term--tags', $normalized['included'][1]['type']);
    $this->assertSame($this->term1->label(), $normalized['included'][1]['attributes']['name']);
    $this->assertCount(12, $normalized['included'][1]['attributes']);
    $this->assertTrue(!isset($normalized['included'][1]['attributes']['created']));
    // Make sure that the cache tags for the includes and the requested entities
    // are bubbling as expected.
    $this->assertArraySubset(
      ['file:1', 'node:1', 'taxonomy_term:1', 'taxonomy_term:2', 'user:1'],
      $jsonapi_doc_object->getCacheTags()
    );
    $this->assertSame(
      Cache::PERMANENT,
      $jsonapi_doc_object->getCacheMaxAge()
    );
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeRelated() {
    $this->markTestIncomplete('This fails and should be fixed by https://www.drupal.org/project/jsonapi/issues/2922121');

    list($request, $resource_type) = $this->generateProphecies('node', 'article', 'uid');
    $request->query = new ParameterBag([
      'fields' => [
        'user--user' => 'name,roles',
      ],
      'include' => 'roles',
    ]);
    $document_wrapper = $this->prophesize(JsonApiDocumentTopLevel::class);
    $author = $this->node->get('uid')->entity;
    $document_wrapper->getData()->willReturn($author);

    $jsonapi_doc_object = $this
      ->getNormalizer()
      ->normalize(
        $document_wrapper->reveal(),
        'api_json',
        [
          'resource_type' => $resource_type,
          'account' => NULL,
        ]
      );
    $normalized = $jsonapi_doc_object->getNormalization();
    $this->assertSame($normalized['data']['attributes']['name'], 'user1');
    $this->assertEquals($normalized['data']['id'], User::load(1)->uuid());
    $this->assertEquals($normalized['data']['type'], 'user--user');
    // Make sure that the cache tags for the includes and the requested entities
    // are bubbling as expected.
    $this->assertSame(['user:1'], $jsonapi_doc_object->getCacheTags());
    $this->assertSame(Cache::PERMANENT, $jsonapi_doc_object->getCacheMaxAge());
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeUuid() {
    list($request, $resource_type) = $this->generateProphecies('node', 'article', 'uuid');
    $resource_object = ResourceObject::createFromEntity($resource_type, $this->node);
    $include_param = 'uid,field_tags';
    $includes = $this->includeResolver->resolve($resource_object, $include_param);
    $document_wrapper = new JsonApiDocumentTopLevel(new ResourceObjectData([$resource_object], 1), $includes, new LinkCollection([]));

    $request->query = new ParameterBag([
      'fields' => [
        'node--article' => 'title,node_type,uid,field_tags',
        'user--user' => 'name',
      ],
      'include' => $include_param,
    ]);

    $jsonapi_doc_object = $this
      ->getNormalizer()
      ->normalize(
        $document_wrapper,
        'api_json',
        [
          'resource_type' => $resource_type,
          'account' => NULL,
          'include' => [
            'uid',
            'field_tags',
          ],
        ]
      );
    $normalized = $jsonapi_doc_object->getNormalization();
    $this->assertStringMatchesFormat($this->node->uuid(), $normalized['data']['id']);
    $this->assertEquals($this->node->type->entity->uuid(), $normalized['data']['relationships']['node_type']['data']['id']);
    $this->assertEquals($this->user->uuid(), $normalized['data']['relationships']['uid']['data']['id']);
    $this->assertFalse(empty($normalized['included'][0]['id']));
    $this->assertTrue(empty($normalized['meta']['omitted']));
    $this->assertEquals($this->user->uuid(), $normalized['included'][0]['id']);
    $this->assertCount(1, $normalized['included'][0]['attributes']);
    $this->assertCount(12, $normalized['included'][1]['attributes']);
    // Make sure that the cache tags for the includes and the requested entities
    // are bubbling as expected.
    $this->assertArraySubset(
      ['node:1', 'taxonomy_term:1', 'taxonomy_term:2', 'user:1'],
      $jsonapi_doc_object->getCacheTags()
    );
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeException() {
    $normalized = $this
      ->container
      ->get('jsonapi.serializer')
      ->normalize(
        new JsonApiDocumentTopLevel(new ErrorCollection([new BadRequestHttpException('Lorem')]), new NullIncludedData(), new LinkCollection([])),
        'api_json',
        []
      )->getNormalization();
    $this->assertNotEmpty($normalized['errors']);
    $this->assertArrayNotHasKey('data', $normalized);
    $this->assertEquals(400, $normalized['errors'][0]['status']);
    $this->assertEquals('Lorem', $normalized['errors'][0]['detail']);
    $this->assertEquals([
      'info' => [
        'href' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.4.1',
      ],
      'via' => ['href' => 'http://localhost/'],
    ], $normalized['errors'][0]['links']);
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeConfig() {
    list($request, $resource_type) = $this->generateProphecies('node_type', 'node_type', 'id');
    $resource_object = ResourceObject::createFromEntity($resource_type, $this->nodeType);
    $document_wrapper = new JsonApiDocumentTopLevel(new ResourceObjectData([$resource_object], 1), new NullIncludedData(), new LinkCollection([]));

    $jsonapi_doc_object = $this
      ->getNormalizer()
      ->normalize($document_wrapper, 'api_json', [
        'resource_type' => $resource_type,
        'account' => NULL,
        'sparse_fieldset' => [
          'node_type--node_type' => [
            'description',
            'display_submitted',
          ],
        ],
      ]);
    $normalized = $jsonapi_doc_object->getNormalization();
    $this->assertSame(['description', 'display_submitted'], array_keys($normalized['data']['attributes']));
    $this->assertSame($normalized['data']['id'], NodeType::load('article')->uuid());
    $this->assertSame($normalized['data']['type'], 'node_type--node_type');
    // Make sure that the cache tags for the includes and the requested entities
    // are bubbling as expected.
    $this->assertSame(['config:node.type.article'], $jsonapi_doc_object->getCacheTags());
  }

  /**
   * Try to POST a node and check if it exists afterwards.
   *
   * @covers ::denormalize
   */
  public function testDenormalize() {
    $payload = '{"data":{"type":"article","attributes":{"title":"Testing article"}}}';

    list($request, $resource_type) = $this->generateProphecies('node', 'article', 'id');
    $node = $this
      ->getNormalizer()
      ->denormalize(Json::decode($payload), NULL, 'api_json', [
        'resource_type' => $resource_type,
      ]);
    $this->assertInstanceOf(Node::class, $node);
    $this->assertSame('Testing article', $node->getTitle());
  }

  /**
   * Try to POST a node and check if it exists afterwards.
   *
   * @covers ::denormalize
   */
  public function testDenormalizeUuid() {
    $configurations = [
      // Good data.
      [
        [
          [$this->term2->uuid(), $this->term1->uuid()],
          $this->user2->uuid(),
        ],
        [
          [$this->term2->id(), $this->term1->id()],
          $this->user2->id(),
        ],
      ],
      // Good data, without any tags.
      [
        [
          [],
          $this->user2->uuid(),
        ],
        [
          [],
          $this->user2->id(),
        ],
      ],
      // Bad data in first tag.
      [
        [
          ['invalid-uuid', $this->term1->uuid()],
          $this->user2->uuid(),
        ],
        [
          [$this->term1->id()],
          $this->user2->id(),
        ],
        'taxonomy_term--tags:invalid-uuid',
      ],
      // Bad data in user and first tag.
      [
        [
          ['invalid-uuid', $this->term1->uuid()],
          'also-invalid-uuid',
        ],
        [
          [$this->term1->id()],
          NULL,
        ],
        'user--user:also-invalid-uuid',
      ],
    ];

    foreach ($configurations as $configuration) {
      list($payload_data, $expected) = $this->denormalizeUuidProviderBuilder($configuration);
      $payload = Json::encode($payload_data);

      list($request, $resource_type) = $this->generateProphecies('node', 'article');
      $this->container->get('request_stack')->push($request);
      try {
        $node = $this
          ->getNormalizer()
          ->denormalize(Json::decode($payload), NULL, 'api_json', [
            'resource_type' => $resource_type,
          ]);
      }
      catch (NotFoundHttpException $e) {
        $non_existing_resource_identifier = $configuration[2];
        $this->assertEquals("The resource identified by `$non_existing_resource_identifier` (given as a relationship item) could not be found.", $e->getMessage());
        continue;
      }

      /* @var \Drupal\node\Entity\Node $node */
      $this->assertInstanceOf(Node::class, $node);
      $this->assertSame('Testing article', $node->getTitle());
      if (!empty($expected['user_id'])) {
        $owner = $node->getOwner();
        $this->assertEquals($expected['user_id'], $owner->id());
      }
      $tags = $node->get('field_tags')->getValue();
      if (!empty($expected['tag_ids'][0])) {
        $this->assertEquals($expected['tag_ids'][0], $tags[0]['target_id']);
      }
      else {
        $this->assertArrayNotHasKey(0, $tags);
      }
      if (!empty($expected['tag_ids'][1])) {
        $this->assertEquals($expected['tag_ids'][1], $tags[1]['target_id']);
      }
      else {
        $this->assertArrayNotHasKey(1, $tags);
      }
    }
  }

  /**
   * Tests denormalization for related resources with missing or invalid types.
   */
  public function testDenormalizeInvalidTypeAndNoType() {
    $payload_data = [
      'data' => [
        'type' => 'node--article',
        'attributes' => [
          'title' => 'Testing article',
          'id' => '33095485-70D2-4E51-A309-535CC5BC0115',
        ],
        'relationships' => [
          'uid' => [
            'data' => [
              'type' => 'user--user',
              'id' => $this->user2->uuid(),
            ],
          ],
          'field_tags' => [
            'data' => [
              [
                'type' => 'foobar',
                'id' => $this->term1->uuid(),
              ],
            ],
          ],
        ],
      ],
    ];

    // Test relationship member with invalid type.
    $payload = Json::encode($payload_data);
    list($request, $resource_type) = $this->generateProphecies('node', 'article');
    $this->container->get('request_stack')->push($request);
    try {
      $this
        ->getNormalizer()
        ->denormalize(Json::decode($payload), NULL, 'api_json', [
          'resource_type' => $resource_type,
        ]);

      $this->fail('No assertion thrown for invalid type');
    }
    catch (BadRequestHttpException $e) {
      $this->assertEquals("Invalid type specified for related resource: 'foobar'", $e->getMessage());
    }

    // Test relationship member with no type.
    unset($payload_data['data']['relationships']['field_tags']['data'][0]['type']);

    $payload = Json::encode($payload_data);
    list($request, $resource_type) = $this->generateProphecies('node', 'article');
    $this->container->get('request_stack')->push($request);
    try {
      $this->container->get('jsonapi_test_normalizers_kernel.jsonapi_document_toplevel')
        ->denormalize(Json::decode($payload), NULL, 'api_json', [
          'resource_type' => $resource_type,
        ]);

      $this->fail('No assertion thrown for missing type');
    }
    catch (BadRequestHttpException $e) {
      $this->assertEquals("No type specified for related resource", $e->getMessage());
    }
  }

  /**
   * We cannot use a PHPUnit data provider because our data depends on $this.
   *
   * @param array $options
   *   Options for how to construct test data.
   *
   * @return array
   *   The test data.
   */
  protected function denormalizeUuidProviderBuilder(array $options) {
    list($input, $expected) = $options;
    list($input_tag_uuids, $input_user_uuid) = $input;
    list($expected_tag_ids, $expected_user_id) = $expected;

    $node = [
      [
        'data' => [
          'type' => 'node--article',
          'attributes' => [
            'title' => 'Testing article',
          ],
          'relationships' => [
            'uid' => [
              'data' => [
                'type' => 'user--user',
                'id' => $input_user_uuid,
              ],
            ],
            'field_tags' => [
              'data' => [],
            ],
          ],
        ],
      ],
      [
        'tag_ids' => $expected_tag_ids,
        'user_id' => $expected_user_id,
      ],
    ];

    if (isset($input_tag_uuids[0])) {
      $node[0]['data']['relationships']['field_tags']['data'][0] = [
        'type' => 'taxonomy_term--tags',
        'id' => $input_tag_uuids[0],
      ];
    }
    if (isset($input_tag_uuids[1])) {
      $node[0]['data']['relationships']['field_tags']['data'][1] = [
        'type' => 'taxonomy_term--tags',
        'id' => $input_tag_uuids[1],
      ];
    }
    return $node;
  }

  /**
   * Ensure that cacheability metadata is properly added.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata $expected_metadata
   *   The expected cacheable metadata.
   * @param array|null $fields
   *   Fields to include in the response, keyed by resource type.
   * @param array|null $includes
   *   Resources paths to include in the response.
   *
   * @dataProvider testCacheableMetadataProvider
   */
  public function testCacheableMetadata(CacheableMetadata $expected_metadata, $fields = NULL, $includes = NULL) {
    list($request, $resource_type) = $this->generateProphecies('node', 'article');
    $resource_object = ResourceObject::createFromEntity($resource_type, $this->node);
    $context = [
      'resource_type' => $resource_type,
      'account' => NULL,
    ];
    $jsonapi_doc_object = $this->getNormalizer()->normalize(new JsonApiDocumentTopLevel(new ResourceObjectData([$resource_object], 1), new NullIncludedData(), new LinkCollection([])), 'api_json', $context);
    $this->assertArraySubset($expected_metadata->getCacheTags(), $jsonapi_doc_object->getCacheTags());
    $this->assertArraySubset($expected_metadata->getCacheContexts(), $jsonapi_doc_object->getCacheContexts());
    $this->assertSame($expected_metadata->getCacheMaxAge(), $jsonapi_doc_object->getCacheMaxAge());
  }

  /**
   * Provides test cases for asserting cacheable metadata behavior.
   */
  public function testCacheableMetadataProvider() {
    $cacheable_metadata = function ($metadata) {
      return CacheableMetadata::createFromRenderArray(['#cache' => $metadata]);
    };

    return [
      [
        $cacheable_metadata(['contexts' => ['languages:language_interface']]),
        ['node--article' => 'body'],
      ],
    ];
  }

  /**
   * Decorates a request with sparse fieldsets and includes.
   */
  protected function decorateRequest(Request $request, array $fields = NULL, array $includes = NULL) {
    $parameters = new ParameterBag();
    $parameters->add($fields ? ['fields' => $fields] : []);
    $parameters->add($includes ? ['include' => $includes] : []);
    $request->query = $parameters;
    return $request;
  }

  /**
   * Helper to load the normalizer.
   */
  protected function getNormalizer() {
    $normalizer_service = $this->container->get('jsonapi_test_normalizers_kernel.jsonapi_document_toplevel');
    // Simulate what happens when this normalizer service is used via the
    // serializer service, as it is meant to be used.
    $normalizer_service->setSerializer($this->container->get('jsonapi.serializer'));
    return $normalizer_service;
  }

  /**
   * Generates the prophecies for the mocked entity request.
   *
   * @param string $entity_type_id
   *   The ID of the entity type. Ex: node.
   * @param string $bundle
   *   The bundle. Ex: article.
   *
   * @return array
   *   A numeric array containing the request and the ResourceType.
   *
   * @throws \Exception
   */
  protected function generateProphecies($entity_type_id, $bundle) {
    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get($entity_type_id, $bundle);

    return [new Request(), $resource_type];
  }

}
