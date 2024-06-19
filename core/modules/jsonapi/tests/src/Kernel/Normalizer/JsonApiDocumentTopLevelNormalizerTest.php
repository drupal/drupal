<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Kernel\Normalizer;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\jsonapi\JsonApiResource\ErrorCollection;
use Drupal\jsonapi\JsonApiResource\LabelOnlyResourceObject;
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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer
 * @group jsonapi
 * @group #slow
 *
 * @internal
 */
class JsonApiDocumentTopLevelNormalizerTest extends JsonapiKernelTestBase {

  use ImageFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
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
    'jsonapi_test_resource_type_building',
  ];

  /**
   * A node to normalize.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $node;

  /**
   * The node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected NodeType $nodeType;

  /**
   * A user to normalize.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * A user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $user2;

  /**
   * A vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected Vocabulary $vocabulary;

  /**
   * A term.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected Term $term1;

  /**
   * A term.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected Term $term2;

  /**
   * The include resolver.
   *
   * @var \Drupal\jsonapi\IncludeResolver
   */
  protected $includeResolver;

  /**
   * The JSON:API resource type repository under test.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * @var \Drupal\file\Entity\File
   */
  private $file;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Add the entity schemas.
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('file');
    // Add the additional table schemas.
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);
    $this->installSchema('file', ['file_usage']);
    $type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
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

    $this->createImageField('field_image', 'node', 'article');

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
      'uid' => $this->user,
      'body' => [
        'format' => 'plain_text',
        'value' => $this->randomString(),
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
      'label' => 'Anonymous',
    ])->save();

    $this->includeResolver = $this->container->get('jsonapi.include_resolver');
    $this->resourceTypeRepository = $this->container->get('jsonapi.resource_type.repository');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
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

    parent::tearDown();
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize(): void {
    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('node', 'article');

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
    $this->assertEquals('1.0', $normalized['jsonapi']['version']);
    $this->assertEquals('http://jsonapi.org/format/1.0/', $normalized['jsonapi']['meta']['links']['self']['href']);

    $this->assertSame($normalized['data']['attributes']['title'], 'dummy_title');
    $this->assertEquals($normalized['data']['id'], $this->node->uuid());
    $this->assertSame([
      'data' => [
        'type' => 'node_type--node_type',
        'id' => NodeType::load('article')->uuid(),
        'meta' => [
          'drupal_internal__target_id' => 'article',
        ],
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
      'drupal_internal__target_id' => $this->file->id(),
    ], $normalized['data']['relationships']['field_image']['data']['meta']);
    $this->assertSame('node--article', $normalized['data']['type']);
    $this->assertEquals([
      'data' => [
        'type' => 'user--user',
        'id' => $this->user->uuid(),
        'meta' => [
          'drupal_internal__target_id' => $this->user->id(),
        ],
      ],
      'links' => [
        'self' => ['href' => Url::fromUri('internal:/jsonapi/node/article/' . $this->node->uuid() . '/relationships/uid', ['query' => ['resourceVersion' => 'id:' . $this->node->getRevisionId()]])->setAbsolute()->toString(TRUE)->getGeneratedUrl()],
        'related' => ['href' => Url::fromUri('internal:/jsonapi/node/article/' . $this->node->uuid() . '/uid', ['query' => ['resourceVersion' => 'id:' . $this->node->getRevisionId()]])->setAbsolute()->toString(TRUE)->getGeneratedUrl()],
      ],
    ], $normalized['data']['relationships']['uid']);
    $this->assertArrayNotHasKey('meta', $normalized);
    $this->assertSame($this->user->uuid(), $normalized['included'][0]['id']);
    $this->assertSame('user--user', $normalized['included'][0]['type']);
    $this->assertSame('user1', $normalized['included'][0]['attributes']['display_name']);
    $this->assertCount(1, $normalized['included'][0]['attributes']);
    $this->assertSame($this->term1->uuid(), $normalized['included'][1]['id']);
    $this->assertSame('taxonomy_term--tags', $normalized['included'][1]['type']);
    $this->assertSame($this->term1->label(), $normalized['included'][1]['attributes']['name']);
    $this->assertCount(11, $normalized['included'][1]['attributes']);
    $this->assertTrue(!isset($normalized['included'][1]['attributes']['created']));
    // Make sure that the cache tags for the includes and the requested entities
    // are bubbling as expected.
    $this->assertEqualsCanonicalizing(
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
  public function testNormalizeUuid(): void {
    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('node', 'article');
    $resource_object = ResourceObject::createFromEntity($resource_type, $this->node);
    $include_param = 'uid,field_tags';
    $includes = $this->includeResolver->resolve($resource_object, $include_param);
    $document_wrapper = new JsonApiDocumentTopLevel(new ResourceObjectData([$resource_object], 1), $includes, new LinkCollection([]));

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
    $this->assertNotEmpty($normalized['included'][0]['id']);
    $this->assertArrayNotHasKey('meta', $normalized);
    $this->assertEquals($this->user->uuid(), $normalized['included'][0]['id']);
    $this->assertCount(1, $normalized['included'][0]['attributes']);
    $this->assertCount(11, $normalized['included'][1]['attributes']);
    // Make sure that the cache tags for the includes and the requested entities
    // are bubbling as expected.
    $this->assertEqualsCanonicalizing(
      ['node:1', 'taxonomy_term:1', 'taxonomy_term:2', 'user:1'],
      $jsonapi_doc_object->getCacheTags()
    );
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeException(): void {
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
   * Tests the message and exceptions when requesting a Label only resource.
   */
  public function testAliasFieldRouteException(): void {
    $this->assertSame('uid', $this->resourceTypeRepository->getByTypeName('node--article')->getPublicName('uid'));
    $this->assertSame('roles', $this->resourceTypeRepository->getByTypeName('user--user')->getPublicName('roles'));
    $resource_type_field_aliases = [
      'node--article' => [
        'uid' => 'author',
      ],
      'user--user' => [
        'roles' => 'user_roles',
      ],
    ];
    \Drupal::state()->set('jsonapi_test_resource_type_builder.resource_type_field_aliases', $resource_type_field_aliases);
    Cache::invalidateTags(['jsonapi_resource_types']);
    $this->assertSame('author', $this->resourceTypeRepository->getByTypeName('node--article')->getPublicName('uid'));
    $this->assertSame('user_roles', $this->resourceTypeRepository->getByTypeName('user--user')->getPublicName('roles'));

    // Create the request to fetch the articles and fetch included user.
    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('node', 'article');
    $user = User::load($this->node->getOwnerId());

    $resource_object = ResourceObject::createFromEntity($resource_type, $this->node);
    $user_resource_type = $this->container->get('jsonapi.resource_type.repository')->get('user', 'user');

    $resource_object_user = LabelOnlyResourceObject::createFromEntity($user_resource_type, $user);
    $includes = $this->includeResolver->resolve($resource_object_user, 'user_roles');

    /** @var \Drupal\jsonapi\Normalizer\Value\CacheableNormalization $jsonapi_doc_object */
    $jsonapi_doc_object = $this
      ->getNormalizer()
      ->normalize(
        new JsonApiDocumentTopLevel(new ResourceObjectData([$resource_object, $resource_object_user], 2), $includes, new LinkCollection([])),
        'api_json',
        [
          'resource_type' => $resource_type,
          'account' => NULL,
          'sparse_fieldset' => [
            'node--article' => [
              'title',
              'node_type',
              'uid',
            ],
            'user--user' => [
              'user_roles',
            ],
          ],
          'include' => [
            'user_roles',
          ],
        ],
      )->getNormalization();
    $this->assertNotEmpty($jsonapi_doc_object['meta']['omitted']);
    foreach ($jsonapi_doc_object['meta']['omitted']['links'] as $key => $link) {
      if (str_starts_with($key, 'item--')) {
        // Ensure that resource link contains URL with the alias field.
        $resource_link = Url::fromUri('internal:/jsonapi/user/user/' . $user->uuid() . '/user_roles')->setAbsolute()->toString(TRUE);
        $this->assertEquals($resource_link->getGeneratedUrl(), $link['href']);
        $this->assertEquals("The current user is not allowed to view this relationship. The user only has authorization for the 'view label' operation.", $link['meta']['detail']);
      }
    }
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeConfig(): void {
    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('node_type', 'node_type');
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
  public function testDenormalize(): void {
    $payload = '{"data":{"type":"article","attributes":{"title":"Testing article"}}}';

    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('node', 'article');
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
  public function testDenormalizeUuid(): void {
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
      [$payload_data, $expected] = $this->denormalizeUuidProviderBuilder($configuration);
      $payload = Json::encode($payload_data);

      $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('node', 'article');
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

      /** @var \Drupal\node\Entity\Node $node */
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
  public function testDenormalizeInvalidTypeAndNoType(): void {
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
    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('node', 'article');
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
    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('node', 'article');
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
    [$input, $expected] = $options;
    [$input_tag_uuids, $input_user_uuid] = $input;
    [$expected_tag_ids, $expected_user_id] = $expected;

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
   *
   * @dataProvider testCacheableMetadataProvider
   */
  public function testCacheableMetadata(CacheableMetadata $expected_metadata): void {
    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('node', 'article');
    $resource_object = ResourceObject::createFromEntity($resource_type, $this->node);
    $context = [
      'resource_type' => $resource_type,
      'account' => NULL,
    ];
    $jsonapi_doc_object = $this->getNormalizer()->normalize(new JsonApiDocumentTopLevel(new ResourceObjectData([$resource_object], 1), new NullIncludedData(), new LinkCollection([])), 'api_json', $context);
    foreach ($expected_metadata->getCacheTags() as $tag) {
      $this->assertContains($tag, $jsonapi_doc_object->getCacheTags());
    }
    foreach ($expected_metadata->getCacheContexts() as $context) {
      $this->assertContains($context, $jsonapi_doc_object->getCacheContexts());
    }
    $this->assertSame($expected_metadata->getCacheMaxAge(), $jsonapi_doc_object->getCacheMaxAge());
  }

  /**
   * Provides test cases for asserting cacheable metadata behavior.
   */
  public static function testCacheableMetadataProvider() {
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
   * Helper to load the normalizer.
   */
  protected function getNormalizer() {
    $normalizer_service = $this->container->get('jsonapi_test_normalizers_kernel.jsonapi_document_toplevel');
    // Simulate what happens when this normalizer service is used via the
    // serializer service, as it is meant to be used.
    $normalizer_service->setSerializer($this->container->get('jsonapi.serializer'));
    return $normalizer_service;
  }

}
