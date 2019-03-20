<?php

namespace Drupal\Tests\jsonapi\Unit\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\jsonapi\Context\FieldResolver;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer
 * @group jsonapi
 *
 * @internal
 */
class JsonApiDocumentTopLevelNormalizerTest extends UnitTestCase {

  /**
   * The normalizer under test.
   *
   * @var \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $resource_type_repository = $this->prophesize(ResourceTypeRepository::class);
    $field_resolver = $this->prophesize(FieldResolver::class);

    $resource_type_repository
      ->getByTypeName(Argument::any())
      ->willReturn(new ResourceType('node', 'article', NULL));

    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $self = $this;
    $uuid_to_id = [
      '76dd5c18-ea1b-4150-9e75-b21958a2b836' => 1,
      'fcce1b61-258e-4054-ae36-244d25a9e04c' => 2,
    ];
    $entity_storage->loadByProperties(Argument::type('array'))
      ->will(function ($args) use ($self, $uuid_to_id) {
        $result = [];
        foreach ($args[0]['uuid'] as $uuid) {
          $entity = $self->prophesize(EntityInterface::class);
          $entity->uuid()->willReturn($uuid);
          $entity->id()->willReturn($uuid_to_id[$uuid]);
          $result[$uuid] = $entity->reveal();
        }
        return $result;
      });
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('node')->willReturn($entity_storage->reveal());
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->getKey('uuid')->willReturn('uuid');
    $entity_type_manager->getDefinition('node')->willReturn($entity_type->reveal());

    $this->normalizer = new JsonApiDocumentTopLevelNormalizer(
      $entity_type_manager->reveal(),
      $resource_type_repository->reveal(),
      $field_resolver->reveal()
    );

    $serializer = $this->prophesize(DenormalizerInterface::class);
    $serializer->willImplement(SerializerInterface::class);
    $serializer->denormalize(
      Argument::type('array'),
      Argument::type('string'),
      Argument::type('string'),
      Argument::type('array')
    )->willReturnArgument(0);

    $this->normalizer->setSerializer($serializer->reveal());
  }

  /**
   * @covers ::denormalize
   * @dataProvider denormalizeProvider
   */
  public function testDenormalize($input, $expected) {
    $resource_type = new ResourceType('node', 'article', FieldableEntityInterface::class);
    $resource_type->setRelatableResourceTypes([]);
    $context = ['resource_type' => $resource_type];
    $denormalized = $this->normalizer->denormalize($input, NULL, 'api_json', $context);
    $this->assertSame($expected, $denormalized);
  }

  /**
   * Data provider for the denormalize test.
   *
   * @return array
   *   The data for the test method.
   */
  public function denormalizeProvider() {
    return [
      [
        [
          'data' => [
            'type' => 'lorem',
            'id' => 'e1a613f6-f2b9-4e17-9d33-727eb6509d8b',
            'attributes' => ['title' => 'dummy_title'],
          ],
        ],
        [
          'title' => 'dummy_title',
          'uuid' => 'e1a613f6-f2b9-4e17-9d33-727eb6509d8b',
        ],
      ],
      [
        [
          'data' => [
            'type' => 'lorem',
            'id' => '0676d1bf-55b3-4bbc-9fbc-3df10f4599d5',
            'relationships' => ['field_dummy' => ['data' => ['type' => 'node', 'id' => '76dd5c18-ea1b-4150-9e75-b21958a2b836']]],
          ],
        ],
        [
          'uuid' => '0676d1bf-55b3-4bbc-9fbc-3df10f4599d5',
          'field_dummy' => [
            [
              'target_id' => 1,
            ],
          ],
        ],
      ],
      [
        [
          'data' => [
            'type' => 'lorem',
            'id' => '535ba297-8d79-4fc1-b0d6-dc2f047765a1',
            'relationships' => [
              'field_dummy' => [
                'data' => [
                  [
                    'type' => 'node',
                    'id' => '76dd5c18-ea1b-4150-9e75-b21958a2b836',
                  ],
                  [
                    'type' => 'node',
                    'id' => 'fcce1b61-258e-4054-ae36-244d25a9e04c',
                  ],
                ],
              ],
            ],
          ],
        ],
        [
          'uuid' => '535ba297-8d79-4fc1-b0d6-dc2f047765a1',
          'field_dummy' => [
            ['target_id' => 1],
            ['target_id' => 2],
          ],
        ],
      ],
      [
        [
          'data' => [
            'type' => 'lorem',
            'id' => '535ba297-8d79-4fc1-b0d6-dc2f047765a1',
            'relationships' => [
              'field_dummy' => [
                'data' => [
                  [
                    'type' => 'node',
                    'id' => '76dd5c18-ea1b-4150-9e75-b21958a2b836',
                    'meta' => ['foo' => 'bar'],
                  ],
                  [
                    'type' => 'node',
                    'id' => 'fcce1b61-258e-4054-ae36-244d25a9e04c',
                  ],
                ],
              ],
            ],
          ],
        ],
        [
          'uuid' => '535ba297-8d79-4fc1-b0d6-dc2f047765a1',
          'field_dummy' => [
            [
              'target_id' => 1,
              'foo' => 'bar',
            ],
            ['target_id' => 2],
          ],
        ],
      ],
    ];
  }

  /**
   * Ensures only valid UUIDs can be specified.
   *
   * @param string $id
   *   The input UUID. May be invalid.
   * @param bool $expect_exception
   *   Whether to expect an exception.
   *
   * @covers ::denormalize
   * @dataProvider denormalizeUuidProvider
   */
  public function testDenormalizeUuid($id, $expect_exception) {
    $data['data'] = (isset($id)) ?
      ['type' => 'node--article', 'id' => $id] :
      ['type' => 'node--article'];

    if ($expect_exception) {
      $this->setExpectedException(
        UnprocessableEntityHttpException::class,
        'IDs should be properly generated and formatted UUIDs as described in RFC 4122.'
      );
    }

    $denormalized = $this->normalizer->denormalize($data, NULL, 'api_json', [
      'resource_type' => new ResourceType(
        'node',
        'article',
        FieldableEntityInterface::class
      ),
    ]);

    if (isset($id)) {
      $this->assertSame($id, $denormalized['uuid']);
    }
    else {
      $this->assertArrayNotHasKey('uuid', $denormalized);
    }
  }

  /**
   * Provides test cases for testDenormalizeUuid.
   */
  public function denormalizeUuidProvider() {
    return [
      'valid' => ['76dd5c18-ea1b-4150-9e75-b21958a2b836', FALSE],
      'missing' => [NULL, FALSE],
      'invalid_empty' => ['', TRUE],
      'invalid_alpha' => ['invalid', TRUE],
      'invalid_numeric' => [1234, TRUE],
      'invalid_alphanumeric' => ['abc123', TRUE],
    ];
  }

}
