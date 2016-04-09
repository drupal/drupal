<?php

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\serialization\Normalizer\EntityNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\EntityNormalizer
 * @group serialization
 */
class EntityNormalizerTest extends UnitTestCase {

  /**
   * The mock entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mock serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $serializer;

  /**
   * The entity normalizer.
   *
   * @var \Drupal\serialization\Normalizer\EntityNormalizer
   */
  protected $entityNormalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->entityNormalizer = new EntityNormalizer($this->entityManager);
  }

  /**
   * Tests the normalize() method.
   *
   * @covers ::normalize
   */
  public function testNormalize() {
    $list_item_1 = $this->getMock('Drupal\Core\TypedData\TypedDataInterface');
    $list_item_2 = $this->getMock('Drupal\Core\TypedData\TypedDataInterface');

    $definitions = array(
      'field_1' => $list_item_1,
      'field_2' => $list_item_2,
    );

    $content_entity = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityBase')
      ->disableOriginalConstructor()
      ->setMethods(array('getFields'))
      ->getMockForAbstractClass();
    $content_entity->expects($this->once())
      ->method('getFields')
      ->will($this->returnValue($definitions));

    $serializer = $this->getMockBuilder('Symfony\Component\Serializer\Serializer')
      ->disableOriginalConstructor()
      ->setMethods(array('normalize'))
      ->getMock();
    $serializer->expects($this->at(0))
      ->method('normalize')
      ->with($list_item_1, 'test_format');
    $serializer->expects($this->at(1))
      ->method('normalize')
      ->with($list_item_2, 'test_format');

    $this->entityNormalizer->setSerializer($serializer);

    $this->entityNormalizer->normalize($content_entity, 'test_format');
  }

  /**
   * Tests the denormalize() method with no entity type provided in context.
   *
   * @covers ::denormalize
   *
   * @expectedException \Symfony\Component\Serializer\Exception\UnexpectedValueException
   */
  public function testDenormalizeWithNoEntityType() {
    $this->entityNormalizer->denormalize(array(), 'Drupal\Core\Entity\ContentEntityBase');
  }

  /**
   * Tests the denormalize method with a bundle property.
   *
   * @covers ::denormalize
   */
  public function testDenormalizeWithValidBundle() {
    $test_data = [
      'key_1' => 'value_1',
      'key_2' => 'value_2',
      'test_type' => [
        ['name' => 'test_bundle'],
      ],
    ];

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->will($this->returnValue(TRUE));
    $entity_type->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->will($this->returnValue('test_type'));
    $entity_type->expects($this->once())
      ->method('getBundleEntityType')
      ->will($this->returnValue('test_bundle'));

    $entity_type_storage_definition = $this->getmock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $entity_type_storage_definition->expects($this->once())
      ->method('getMainPropertyName')
      ->will($this->returnValue('name'));

    $entity_type_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $entity_type_definition->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->will($this->returnValue($entity_type_storage_definition));

    $base_definitions = [
      'test_type' => $entity_type_definition,
    ];

    $this->entityManager->expects($this->at(0))
      ->method('getDefinition')
      ->with('test')
      ->will($this->returnValue($entity_type));
    $this->entityManager->expects($this->at(1))
      ->method('getBaseFieldDefinitions')
      ->with('test')
      ->will($this->returnValue($base_definitions));

    $entity_query_mock = $this->getMock('Drupal\Core\Entity\Query\QueryInterface');
    $entity_query_mock->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(['test_bundle' => 'test_bundle']));

    $entity_type_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $entity_type_storage->expects($this->once())
      ->method('getQuery')
      ->will($this->returnValue($entity_query_mock));

    $this->entityManager->expects($this->at(2))
      ->method('getStorage')
      ->with('test_bundle')
      ->will($this->returnValue($entity_type_storage));

    // The expected test data should have a modified test_type property.
    $expected_test_data = array(
      'key_1' => 'value_1',
      'key_2' => 'value_2',
      'test_type' => 'test_bundle',
    );

    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('create')
      ->with($expected_test_data)
      ->will($this->returnValue($this->getMock('Drupal\Core\Entity\EntityInterface')));

    $this->entityManager->expects($this->at(3))
      ->method('getStorage')
      ->with('test')
      ->will($this->returnValue($storage));

    $this->assertNotNull($this->entityNormalizer->denormalize($test_data, 'Drupal\Core\Entity\ContentEntityBase', NULL, ['entity_type' => 'test']));
  }

  /**
   * Tests the denormalize method with a bundle property.
   *
   * @expectedException \Symfony\Component\Serializer\Exception\UnexpectedValueException
   *
   * @covers ::denormalize
   */
  public function testDenormalizeWithInvalidBundle() {
    $test_data = [
      'key_1' => 'value_1',
      'key_2' => 'value_2',
      'test_type' => [
        ['name' => 'test_bundle'],
      ],
    ];

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->will($this->returnValue(TRUE));
    $entity_type->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->will($this->returnValue('test_type'));
    $entity_type->expects($this->once())
      ->method('getBundleEntityType')
      ->will($this->returnValue('test_bundle'));

    $entity_type_storage_definition = $this->getmock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $entity_type_storage_definition->expects($this->once())
      ->method('getMainPropertyName')
      ->will($this->returnValue('name'));

    $entity_type_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $entity_type_definition->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->will($this->returnValue($entity_type_storage_definition));

    $base_definitions = [
      'test_type' => $entity_type_definition,
    ];

    $this->entityManager->expects($this->at(0))
      ->method('getDefinition')
      ->with('test')
      ->will($this->returnValue($entity_type));
    $this->entityManager->expects($this->at(1))
      ->method('getBaseFieldDefinitions')
      ->with('test')
      ->will($this->returnValue($base_definitions));

    $entity_query_mock = $this->getMock('Drupal\Core\Entity\Query\QueryInterface');
    $entity_query_mock->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(['test_bundle_other' => 'test_bundle_other']));

    $entity_type_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $entity_type_storage->expects($this->once())
      ->method('getQuery')
      ->will($this->returnValue($entity_query_mock));

    $this->entityManager->expects($this->at(2))
      ->method('getStorage')
      ->with('test_bundle')
      ->will($this->returnValue($entity_type_storage));


    $this->entityNormalizer->denormalize($test_data, 'Drupal\Core\Entity\ContentEntityBase', NULL, ['entity_type' => 'test']);
  }

  /**
   * Tests the denormalize method with no bundle defined.
   *
   * @covers ::denormalize
   */
  public function testDenormalizeWithNoBundle() {
    $test_data = array(
      'key_1' => 'value_1',
      'key_2' => 'value_2',
    );

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->will($this->returnValue(FALSE));
    $entity_type->expects($this->never())
      ->method('getKey');

    $this->entityManager->expects($this->once())
      ->method('getDefinition')
      ->with('test')
      ->will($this->returnValue($entity_type));

    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('create')
      ->with($test_data)
      ->will($this->returnValue($this->getMock('Drupal\Core\Entity\EntityInterface')));

    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->with('test')
      ->will($this->returnValue($storage));

    $this->entityManager->expects($this->never())
      ->method('getBaseFieldDefinitions');

    $this->assertNotNull($this->entityNormalizer->denormalize($test_data, 'Drupal\Core\Entity\ContentEntityBase', NULL, array('entity_type' => 'test')));
  }

}
