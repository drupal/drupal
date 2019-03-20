<?php

namespace Drupal\Tests\jsonapi\Kernel\Serializer;

use Drupal\Core\Render\Markup;
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
  public static $modules = [
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
   * The subject under test.
   *
   * @var \Drupal\jsonapi\Serializer\Serializer
   */
  protected $sut;

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
    $this->user = User::create([
      'name' => $this->randomString(),
      'status' => 1,
    ]);
    $this->user->save();
    NodeType::create([
      'type' => 'foo',
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
    $this->sut = $this->container->get('sut');
  }

  /**
   * @covers \Drupal\jsonapi\Serializer\Serializer::normalize
   */
  public function testFallbackNormalizer() {
    $context = ['account' => $this->user];

    $value = $this->sut->normalize($this->node->field_text, 'api_json', $context);
    $this->assertTrue($value instanceof CacheableNormalization);

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
    $this->assertTrue($value[0] instanceof CacheableNormalization);

    // Continue to use the fallback normalizer when we need it.
    $data = Markup::create('<h2>Test Markup</h2>');
    $value = $this->sut->normalize($data, 'api_json', $context);

    $this->assertEquals('<h2>Test Markup</h2>', $value);
  }

}
