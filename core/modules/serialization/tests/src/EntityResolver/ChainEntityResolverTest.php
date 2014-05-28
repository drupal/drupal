<?php

/**
 * @file
 * Contains \Drupal\serialization\Tests\EntityResolver\ChainEntityResolverTest.
 */

namespace Drupal\serialization\Tests\EntityResolver;

use Drupal\Tests\UnitTestCase;
use Drupal\serialization\EntityResolver\ChainEntityResolver;

/**
 * Tests the ChainEntityResolver class.
 *
 * @see \Drupal\serialization\EntityResolver\ChainEntityResolver
 *
 * @group Drupal
 * @group Serialization
 */
class ChainEntityResolverTest extends UnitTestCase {

  /**
   * A mocked normalizer.
   *
   * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $testNormalizer;

  /**
   * Test data passed to the resolve method.
   *
   * @var \stdClass
   */
  protected $testData;

  /**
   * A test entity type.
   *
   * @var string
   */
  protected $testEntityType = 'test_type';

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'ChainEntityResolver',
      'description' => 'Tests the Drupal\serialization\EntityResolver\ChainEntityResolver class.',
      'group' => 'Serialization',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->testNormalizer = $this->getMock('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    $this->testData = new \stdClass();
  }

  /**
   * Test the resolve method with no matching resolvers.
   */
  public function testResolverWithNoneResolved() {
    $resolvers = array(
      $this->createEntityResolverMock(),
      $this->createEntityResolverMock(),
    );

    $resolver = new ChainEntityResolver($resolvers);

    $this->assertNull($resolver->resolve($this->testNormalizer, $this->testData, $this->testEntityType));
  }

  /**
   * Test the resolve method with a matching resolver first.
   */
  public function testResolverWithFirstResolved() {
    $resolvers = array(
      $this->createEntityResolverMock(10),
      $this->createEntityResolverMock(NULL, FALSE),
    );

    $resolver = new ChainEntityResolver($resolvers);

    $this->assertSame(10, $resolver->resolve($this->testNormalizer, $this->testData, $this->testEntityType));
  }

  /**
   * Test the resolve method with a matching resolver last.
   */
  public function testResolverWithLastResolved() {
    $resolvers = array(
      $this->createEntityResolverMock(),
      $this->createEntityResolverMock(10),
    );

    $resolver = new ChainEntityResolver($resolvers);

    $this->assertSame(10, $resolver->resolve($this->testNormalizer, $this->testData, $this->testEntityType));
  }

  /**
   * Creates a mock entity resolver.
   *
   * @param null|int $return
   *   Whether the mocked resolve method should return TRUE or FALSE.
   *
   * @param bool $called
   *   Whether or not the resolve method is expected to be called.
   *
   * @return \Drupal\serialization\EntityResolver\EntityResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   *   The mocked entity ressolver.
   */
  protected function createEntityResolverMock($return = NULL, $called = TRUE) {
    $mock = $this->getMock('Drupal\serialization\EntityResolver\EntityResolverInterface');

    if ($called) {
      $mock->expects($this->once())
        ->method('resolve')
        ->with($this->testNormalizer, $this->testData, $this->testEntityType)
        ->will($this->returnValue($return));
    }
    else {
      $mock->expects($this->never())
        ->method('resolve');
    }

    return $mock;
  }

}
