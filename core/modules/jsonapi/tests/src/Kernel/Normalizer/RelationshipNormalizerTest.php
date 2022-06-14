<?php

namespace Drupal\Tests\jsonapi\Kernel\Normalizer;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\jsonapi\JsonApiResource\Relationship;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\RelationshipNormalizer;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\RelationshipNormalizer
 * @group jsonapi
 *
 * @internal
 */
class RelationshipNormalizerTest extends JsonapiKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'image',
    'jsonapi',
    'node',
    'serialization',
    'system',
    'user',
  ];

  /**
   * Static UUID for the referencing entity.
   *
   * @var string
   */
  protected static $referencerId = '2c344ae5-4303-4f17-acd4-e20d2a9a6c44';

  /**
   * Static UUIDs for use in tests.
   *
   * @var string[]
   */
  protected static $userIds = [
    '457fed75-a3ed-4e9e-823c-f9aeff6ec8ca',
    '67e4063f-ac74-46ac-ac5f-07efda9fd551',
  ];

  /**
   * Static UIDs for use in tests.
   *
   * @var string[]
   */
  protected static $userUids = [
    10,
    11,
  ];

  /**
   * Static UUIDs for use in tests.
   *
   * @var string[]
   */
  protected static $imageIds = [
    '71e67249-df4a-4616-9065-4cc2e812235b',
    'ce5093fc-417f-477d-932d-888407d5cbd5',
  ];
  /**
   * Static UUIDs for use in tests.
   *
   * @var string[]
   */
  protected static $imageUids = [
    1,
    2,
  ];

  /**
   * A user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $user1;

  /**
   * A user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $user2;

  /**
   * An image.
   *
   * @var \Drupal\file\Entity\File
   */
  protected File $image1;

  /**
   * An image.
   *
   * @var \Drupal\file\Entity\File
   */
  protected File $image2;

  /**
   * A referencer node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected Node $referencer;

  /**
   * The node type.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceType
   */
  protected ResourceType $referencingResourceType;

  /**
   * The normalizer.
   *
   * @var \Drupal\jsonapi\Normalizer\RelationshipNormalizer
   */
  protected RelationshipNormalizer $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up the data model.
    // Add the entity schemas.
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');

    // Add the additional table schemas.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('file', ['file_usage']);
    NodeType::create([
      'type' => 'referencer',
    ])->save();
    $this->createEntityReferenceField('node', 'referencer', 'field_user', 'User', 'user', 'default', ['target_bundles' => NULL], 1);
    $this->createEntityReferenceField('node', 'referencer', 'field_users', 'Users', 'user', 'default', ['target_bundles' => NULL], FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $field_storage_config = [
      'type' => 'image',
      'entity_type' => 'node',
    ];
    FieldStorageConfig::create(['field_name' => 'field_image', 'cardinality' => 1] + $field_storage_config)->save();
    FieldStorageConfig::create(['field_name' => 'field_images', 'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED] + $field_storage_config)->save();
    $field_config = [
      'entity_type' => 'node',
      'bundle' => 'referencer',
    ];
    FieldConfig::create(['field_name' => 'field_image', 'label' => 'Image'] + $field_config)->save();
    FieldConfig::create(['field_name' => 'field_images', 'label' => 'Images'] + $field_config)->save();

    // Set up the test data.
    $this->setUpCurrentUser([], ['access content']);
    $this->user1 = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
      'uuid' => static::$userIds[0],
      'uid'  => static::$userUids[0],
    ]);
    $this->user1->save();
    $this->user2 = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
      'uuid' => static::$userIds[1],
      'uid'  => static::$userUids[1],
    ]);
    $this->user2->save();

    $this->image1 = File::create([
      'uri' => 'public:/image1.png',
      'uuid' => static::$imageIds[0],
      'uid'  => static::$imageUids[0],
    ]);
    $this->image1->save();
    $this->image2 = File::create([
      'uri' => 'public:/image2.png',
      'uuid' => static::$imageIds[1],
      'uid'  => static::$imageUids[1],
    ]);
    $this->image2->save();

    // Create the node from which all the previously created entities will be
    // referenced.
    $this->referencer = Node::create([
      'title' => 'Referencing node',
      'type' => 'referencer',
      'status' => 1,
      'uuid' => static::$referencerId,
    ]);
    $this->referencer->save();

    // Set up the test dependencies.
    $this->referencingResourceType = $this->container->get('jsonapi.resource_type.repository')->get('node', 'referencer');
    $this->normalizer = new RelationshipNormalizer();
    $this->normalizer->setSerializer($this->container->get('jsonapi.serializer'));
  }

  /**
   * @covers ::normalize
   * @dataProvider normalizeProvider
   */
  public function testNormalize($entity_property_names, $field_name, $expected) {
    // Links cannot be generated in the test provider because the container
    // has not yet been set.
    $expected['links'] = [
      'self' => ['href' => Url::fromUri('base:/jsonapi/node/referencer/' . static::$referencerId . "/relationships/$field_name", ['query' => ['resourceVersion' => 'id:1']])->setAbsolute()->toString()],
      'related' => ['href' => Url::fromUri('base:/jsonapi/node/referencer/' . static::$referencerId . "/$field_name", ['query' => ['resourceVersion' => 'id:1']])->setAbsolute()->toString()],
    ];
    // Set up different field values.
    $this->referencer->{$field_name} = array_map(function ($entity_property_name) {
      $value = ['target_id' => $this->{$entity_property_name === 'image1a' ? 'image1' : $entity_property_name}->id()];
      switch ($entity_property_name) {
        case 'image1':
          $value['alt'] = 'Cute llama';
          $value['title'] = 'My spirit animal';
          break;

        case 'image1a':
          $value['alt'] = 'Ugly llama';
          $value['title'] = 'My alter ego';
          break;

        case 'image2':
          $value['alt'] = 'Adorable llama';
          $value['title'] = 'My spirit animal 😍';
          break;
      }
      return $value;
    }, $entity_property_names);
    $resource_object = ResourceObject::createFromEntity($this->referencingResourceType, $this->referencer);
    $relationship = Relationship::createFromEntityReferenceField($resource_object, $resource_object->getField($field_name));
    // Normalize.
    $actual = $this->normalizer->normalize($relationship, 'api_json');
    // Assert.
    assert($actual instanceof CacheableNormalization);
    $this->assertEquals($expected, $actual->getNormalization());
  }

  /**
   * Data provider for testNormalize.
   */
  public function normalizeProvider() {
    return [
      'single cardinality' => [
        ['user1'],
        'field_user',
        [
          'data' => [
            'type' => 'user--user',
            'id' => static::$userIds[0],
            'meta' => [
              'drupal_internal__target_id' => static::$userUids[0],
            ],
          ],
        ],
      ],
      'multiple cardinality' => [
        ['user1', 'user2'], 'field_users', [
          'data' => [
            [
              'type' => 'user--user',
              'id' => static::$userIds[0],
              'meta' => [
                'drupal_internal__target_id' => static::$userUids[0],
              ],
            ],
            [
              'type' => 'user--user',
              'id' => static::$userIds[1],
              'meta' => [
                'drupal_internal__target_id' => static::$userUids[1],
              ],
            ],
          ],
        ],
      ],
      'multiple cardinality, all same values' => [
        ['user1', 'user1'], 'field_users', [
          'data' => [
            [
              'type' => 'user--user',
              'id' => static::$userIds[0],
              'meta' => [
                'arity' => 0,
                'drupal_internal__target_id' => static::$userUids[0],
              ],
            ],
            [
              'type' => 'user--user',
              'id' => static::$userIds[0],
              'meta' => [
                'arity' => 1,
                'drupal_internal__target_id' => static::$userUids[0],
              ],
            ],
          ],
        ],
      ],
      'multiple cardinality, some same values' => [
        ['user1', 'user2', 'user1'], 'field_users', [
          'data' => [
            [
              'type' => 'user--user',
              'id' => static::$userIds[0],
              'meta' => [
                'arity' => 0,
                'drupal_internal__target_id' => static::$userUids[0],
              ],
            ],
            [
              'type' => 'user--user',
              'id' => static::$userIds[1],
              'meta' => [
                'drupal_internal__target_id' => static::$userUids[1],
              ],
            ],
            [
              'type' => 'user--user',
              'id' => static::$userIds[0],
              'meta' => [
                'arity' => 1,
                'drupal_internal__target_id' => static::$userUids[0],
              ],
            ],
          ],
        ],
      ],
      'single cardinality, with meta' => [
        ['image1'], 'field_image', [
          'data' => [
            'type' => 'file--file',
            'id' => static::$imageIds[0],
            'meta' => [
              'alt' => 'Cute llama',
              'title' => 'My spirit animal',
              'width' => NULL,
              'height' => NULL,
              'drupal_internal__target_id' => static::$imageUids[0],
            ],
          ],
        ],
      ],
      'multiple cardinality, all same values, with meta' => [
        ['image1', 'image1'], 'field_images', [
          'data' => [
            [
              'type' => 'file--file',
              'id' => static::$imageIds[0],
              'meta' => [
                'alt' => 'Cute llama',
                'title' => 'My spirit animal',
                'width' => NULL,
                'height' => NULL,
                'arity' => 0,
                'drupal_internal__target_id' => static::$imageUids[0],
              ],
            ],
            [
              'type' => 'file--file',
              'id' => static::$imageIds[0],
              'meta' => [
                'alt' => 'Cute llama',
                'title' => 'My spirit animal',
                'width' => NULL,
                'height' => NULL,
                'arity' => 1,
                'drupal_internal__target_id' => static::$imageUids[0],
              ],
            ],
          ],
        ],
      ],
      'multiple cardinality, some same values with same values but different meta' => [
        ['image1', 'image1', 'image1a'], 'field_images', [
          'data' => [
            [
              'type' => 'file--file',
              'id' => static::$imageIds[0],
              'meta' => [
                'alt' => 'Cute llama',
                'title' => 'My spirit animal',
                'width' => NULL,
                'height' => NULL,
                'arity' => 0,
                'drupal_internal__target_id' => static::$imageUids[0],
              ],
            ],
            [
              'type' => 'file--file',
              'id' => static::$imageIds[0],
              'meta' => [
                'alt' => 'Cute llama',
                'title' => 'My spirit animal',
                'width' => NULL,
                'height' => NULL,
                'arity' => 1,
                'drupal_internal__target_id' => static::$imageUids[0],
              ],
            ],
            [
              'type' => 'file--file',
              'id' => static::$imageIds[0],
              'meta' => [
                'alt' => 'Ugly llama',
                'title' => 'My alter ego',
                'width' => NULL,
                'height' => NULL,
                'arity' => 2,
                'drupal_internal__target_id' => static::$imageUids[0],
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
