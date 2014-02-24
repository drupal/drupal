<?php

/**
 * @file
 * Contains \Drupal\serialization\Tests\Normalizer\EntityNormalizerTest.
 */

namespace Drupal\serialization\Tests\Normalizer;

use Drupal\serialization\Normalizer\EntityNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the EntityNormalizer class.
 *
 * @coversDefaultClass \Drupal\serialization\Normalizer\EntityNormalizer
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
  public static function getInfo() {
    return array(
      'name' => 'ListNormalizer',
      'description' => 'Tests the ListNormalizer class.',
      'group' => 'Serialization',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
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
      ->setMethods(array('getProperties'))
      ->getMockForAbstractClass();
    $content_entity->expects($this->once())
      ->method('getProperties')
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
  public function testDenormalizeWithBundle() {
    $test_data = array(
      'key_1' => 'value_1',
      'key_2' => 'value_2',
      'test_type' => array(
        array('value' => 'test_bundle'),
      ),
    );

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->will($this->returnValue(TRUE));
    $entity_type->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->will($this->returnValue('test_type'));

    $this->entityManager->expects($this->once())
      ->method('getDefinition')
      ->with('test')
      ->will($this->returnValue($entity_type));

    // The expected test data should have a modified test_type property.
    $expected_test_data = array(
      'key_1' => 'value_1',
      'key_2' => 'value_2',
      'test_type' => 'test_bundle',
    );

    $storage_controller = $this->getMock('Drupal\Core\Entity\EntityStorageControllerInterface');
    $storage_controller->expects($this->once())
      ->method('create')
      ->with($expected_test_data)
      ->will($this->returnValue($this->getMock('Drupal\Core\Entity\EntityInterface')));

    $this->entityManager->expects($this->once())
      ->method('getStorageController')
      ->with('test')
      ->will($this->returnValue($storage_controller));

    $this->assertNotNull($this->entityNormalizer->denormalize($test_data, 'Drupal\Core\Entity\ContentEntityBase', NULL, array('entity_type' => 'test')));
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

    $storage_controller = $this->getMock('Drupal\Core\Entity\EntityStorageControllerInterface');
    $storage_controller->expects($this->once())
      ->method('create')
      ->with($test_data)
      ->will($this->returnValue($this->getMock('Drupal\Core\Entity\EntityInterface')));

    $this->entityManager->expects($this->once())
      ->method('getStorageController')
      ->with('test')
      ->will($this->returnValue($storage_controller));

    $this->assertNotNull($this->entityNormalizer->denormalize($test_data, 'Drupal\Core\Entity\ContentEntityBase', NULL, array('entity_type' => 'test')));
  }

}
