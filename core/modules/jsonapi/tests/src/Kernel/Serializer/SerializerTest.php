<?php

namespace Drupal\Tests\jsonapi\Kernel\Serializer;

use Drupal\Core\Render\Markup;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi_test_data_type\TraversableObject;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the JSON:API serializer.
 *
 * @coversClass \Drupal\jsonapi\Serializer\Serializer
 * @group jsonapi
 *
 * @internal
 */
class SerializerTest extends JsonapiKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'serialization',
    'system',
    'node',
    'user',
    'field',
    'text',
    'filter',
    'jsonapi_test_data_type',
  ];

  /**
   * An entity for testing.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $node;

  /**
   * A resource type for testing.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceType
   */
  protected $resourceType;

  /**
   * The subject under test.
   *
   * @var \Drupal\jsonapi\Serializer\Serializer
   */
  protected $sut;

  /**
   * A user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $user;

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
    $this->user = User::create([
      'name' => $this->randomString(),
      'status' => 1,
    ]);
    $this->user->save();
    NodeType::create([
      'type' => 'foo',
      'name' => 'Foo',
    ])->save();
    $this->createTextField('node', 'foo', 'field_text', 'Text');
    $this->node = Node::create([
      'title' => 'Test Node',
      'type' => 'foo',
      'field_text' => [
        'value' => 'This is some text.',
        'format' => 'text_plain',
      ],
      'uid' => $this->user->id(),
    ]);
    $this->node->save();
    $this->container->setAlias('sut', 'jsonapi.serializer');
    $this->resourceType = $this->container->get('jsonapi.resource_type.repository')->get($this->node->getEntityTypeId(), $this->node->bundle());
    $this->sut = $this->container->get('sut');
  }

  /**
   * @covers \Drupal\jsonapi\Serializer\Serializer::normalize
   */
  public function testFallbackNormalizer() {
    $context = [
      'account' => $this->user,
      'resource_object' => ResourceObject::createFromEntity($this->resourceType, $this->node),
    ];

    $value = $this->sut->normalize($this->node->field_text, 'api_json', $context);
    $this->assertInstanceOf(CacheableNormalization::class, $value);

    $nested_field = [
      $this->node->field_text,
    ];

    // When an object implements \IteratorAggregate and has corresponding
    // fallback normalizer, it should be normalized by fallback normalizer.
    $traversableObject = new TraversableObject();
    $value = $this->sut->normalize($traversableObject, 'api_json', $context);
    $this->assertEquals($traversableObject->property, $value);

    // When wrapped in an array, we should still be using the JSON:API
    // serializer.
    $value = $this->sut->normalize($nested_field, 'api_json', $context);
    $this->assertInstanceOf(CacheableNormalization::class, $value[0]);

    // Continue to use the fallback normalizer when we need it.
    $data = Markup::create('<h2>Test Markup</h2>');
    $value = $this->sut->normalize($data, 'api_json', $context);

    $this->assertEquals('<h2>Test Markup</h2>', $value);
  }

}
