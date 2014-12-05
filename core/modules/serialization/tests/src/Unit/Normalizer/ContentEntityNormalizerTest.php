<?php

/**
 * @file
 * Contains \Drupal\Tests\serialization\Unit\Normalizer\ContentEntityNormalizerTest.
 */

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\serialization\Normalizer\ContentEntityNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\ContentEntityNormalizer
 * @group serialization
 */
class ContentEntityNormalizerTest extends UnitTestCase {

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
   * The normalizer under test.
   *
   * @var \Drupal\serialization\Normalizer\ContentEntityNormalizer
   */
  protected $contentEntityNormalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->contentEntityNormalizer = new ContentEntityNormalizer($this->entityManager);
    $this->serializer = $this->getMockBuilder('Symfony\Component\Serializer\Serializer')
      ->disableOriginalConstructor()
      ->setMethods(array('normalize'))
      ->getMock();
    $this->contentEntityNormalizer->setSerializer($this->serializer);
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $content_mock = $this->getMock('Drupal\Core\Entity\ContentEntityInterface');
    $config_mock = $this->getMock('Drupal\Core\Entity\ConfigEntityInterface');
    $this->assertTrue($this->contentEntityNormalizer->supportsNormalization($content_mock));
    $this->assertFalse($this->contentEntityNormalizer->supportsNormalization($config_mock));
  }

  /**
   * Tests the normalize() method.
   *
   * @covers ::normalize
   */
  public function testNormalize() {
    $this->serializer->expects($this->any())
      ->method('normalize')
      ->with($this->containsOnlyInstancesOf('Drupal\Core\Field\FieldItemListInterface'), 'test_format', ['account' => NULL])
      ->will($this->returnValue('test'));

    $definitions = array(
      'field_1' => $this->createMockFieldListItem(),
      'field_2' => $this->createMockFieldListItem(FALSE),
    );
    $content_entity_mock = $this->createMockForContentEntity($definitions);

    $normalized = $this->contentEntityNormalizer->normalize($content_entity_mock, 'test_format');

    $this->assertArrayHasKey('field_1', $normalized);
    $this->assertEquals('test', $normalized['field_1']);
    $this->assertArrayNotHasKey('field_2', $normalized);
  }

  /**
   * Tests the normalize() method with account context passed.
   *
   * @covers ::normalize
   */
  public function testNormalizeWithAccountContext() {
    $mock_account = $this->getMock('Drupal\Core\Session\AccountInterface');

    $context = [
      'account' => $mock_account,
    ];

    $this->serializer->expects($this->any())
      ->method('normalize')
      ->with($this->containsOnlyInstancesOf('Drupal\Core\Field\FieldItemListInterface'), 'test_format', $context)
      ->will($this->returnValue('test'));

    // The mock account should get passed directly into the access() method on
    // field items from $context['account'].
    $definitions = array(
      'field_1' => $this->createMockFieldListItem(TRUE, $mock_account),
      'field_2' => $this->createMockFieldListItem(FALSE, $mock_account),
    );
    $content_entity_mock = $this->createMockForContentEntity($definitions);

    $normalized = $this->contentEntityNormalizer->normalize($content_entity_mock, 'test_format', $context);

    $this->assertArrayHasKey('field_1', $normalized);
    $this->assertEquals('test', $normalized['field_1']);
    $this->assertArrayNotHasKey('field_2', $normalized);
  }

  /**
   * Creates a mock content entity.
   *
   * @param $definitions
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  public function createMockForContentEntity($definitions) {
    $content_entity_mock = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityBase')
      ->disableOriginalConstructor()
      ->setMethods(array('getFields'))
      ->getMockForAbstractClass();
    $content_entity_mock->expects($this->once())
      ->method('getFields')
      ->will($this->returnValue($definitions));

    return $content_entity_mock;
  }

  /**
   * Creates a mock field list item.
   *
   * @param bool $access
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function createMockFieldListItem($access = TRUE, $user_context = NULL) {
    $mock = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $mock->expects($this->once())
      ->method('access')
      ->with('view', $user_context)
      ->will($this->returnValue($access));

    return $mock;
  }

}
