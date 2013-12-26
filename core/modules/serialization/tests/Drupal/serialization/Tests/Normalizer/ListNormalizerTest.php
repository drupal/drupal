<?php

/**
 * @file
 * Contains \Drupal\serialization\Tests\Normalizer\ListNormalizerTest.
 */

namespace Drupal\serialization\Tests\Normalizer;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Tests\UnitTestCase;
use Drupal\serialization\Normalizer\ListNormalizer;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;

/**
 * Tests the ListNormalizer class.
 *
 * @see \Drupal\serialization\Normalizer\ListNormalizer
 *
 * @group Drupal
 * @group Serialization
 */
class ListNormalizerTest extends UnitTestCase {

  /**
   * The ListNormalizer instance.
   *
   * @var \Drupal\serialization\Normalizer\ListNormalizer
   */
  protected $normalizer;

  /**
   * The mock list instance.
   *
   * @var \Drupal\Core\TypedData\ListInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $list;

  /**
   * The expected list values to use for testing.
   *
   * @var array
   */
  protected $expectedListValues = array('test', 'test', 'test');

  public static function getInfo() {
    return array(
      'name' => 'ListNormalizer',
      'description' => 'Tests the ListNormalizer class.',
      'group' => 'Serialization',
    );
  }

  public function setUp() {
    // Mock the TypedDataManager to return a TypedDataInterface mock.
    $typed_data = $this->getMock('Drupal\Core\TypedData\TypedDataInterface');
    $typed_data_manager = $this->getMockBuilder('Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->setMethods(array('getPropertyInstance'))
      ->getMock();
    $typed_data_manager->expects($this->any())
      ->method('getPropertyInstance')
      ->will($this->returnValue($typed_data));

    // Set up a mock container as ItemList() will call for the 'typed_data_manager'
    // service.
    $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
      ->setMethods(array('get'))
      ->getMock();
    $container->expects($this->any())
      ->method('get')
      ->with($this->equalTo('typed_data_manager'))
      ->will($this->returnValue($typed_data_manager));

    \Drupal::setContainer($container);

    $this->normalizer = new ListNormalizer();

    $this->list = new ItemList(new DataDefinition());
    $this->list->setValue($this->expectedListValues);
  }

  /**
   * Tests the supportsNormalization() method.
   */
  public function testSupportsNormalization() {
    $this->assertTrue($this->normalizer->supportsNormalization($this->list));
    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
  }

  /**
   * Tests the normalize() method.
   */
  public function testNormalize() {
    $serializer = $this->getMockBuilder('Symfony\Component\Serializer\Serializer')
      ->setMethods(array('normalize'))
      ->getMock();
    $serializer->expects($this->exactly(3))
      ->method('normalize')
      ->will($this->returnValue('test'));

    $this->normalizer->setSerializer($serializer);

    $normalized = $this->normalizer->normalize($this->list);

    $this->assertEquals($this->expectedListValues, $normalized);
  }

}
