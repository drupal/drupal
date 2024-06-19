<?php

declare(strict_types=1);

namespace Drupal\Tests\serialization\Unit\EntityResolver;

use Drupal\Tests\UnitTestCase;
use Drupal\serialization\EntityResolver\ChainEntityResolver;

/**
 * @coversDefaultClass \Drupal\serialization\EntityResolver\ChainEntityResolver
 * @group serialization
 */
class ChainEntityResolverTest extends UnitTestCase {

  /**
   * A mocked normalizer.
   *
   * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $testNormalizer;

  /**
   * Test data passed to the resolve method.
   *
   * @var object
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
  protected function setUp(): void {
    parent::setUp();

    $this->testNormalizer = $this->createMock('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    $this->testData = new \stdClass();
  }

  /**
   * Tests the resolve method with no matching resolvers.
   *
   * @covers ::__construct
   * @covers ::resolve
   */
  public function testResolverWithNoneResolved(): void {
    $resolvers = [
      $this->createEntityResolverMock(),
      $this->createEntityResolverMock(),
    ];

    $resolver = new ChainEntityResolver($resolvers);

    $this->assertNull($resolver->resolve($this->testNormalizer, $this->testData, $this->testEntityType));
  }

  /**
   * Tests the resolve method with no matching resolvers, using addResolver.
   *
   * @covers ::addResolver
   * @covers ::resolve
   */
  public function testResolverWithNoneResolvedUsingAddResolver(): void {
    $resolver = new ChainEntityResolver();
    $resolver->addResolver($this->createEntityResolverMock());
    $resolver->addResolver($this->createEntityResolverMock());

    $this->assertNull($resolver->resolve($this->testNormalizer, $this->testData, $this->testEntityType));
  }

  /**
   * Tests the resolve method with a matching resolver first.
   *
   * @covers ::__construct
   * @covers ::resolve
   */
  public function testResolverWithFirstResolved(): void {
    $resolvers = [
      $this->createEntityResolverMock(10),
      $this->createEntityResolverMock(NULL, FALSE),
    ];

    $resolver = new ChainEntityResolver($resolvers);

    $this->assertSame(10, $resolver->resolve($this->testNormalizer, $this->testData, $this->testEntityType));
  }

  /**
   * Tests the resolve method with a matching resolver last.
   *
   * @covers ::__construct
   * @covers ::resolve
   */
  public function testResolverWithLastResolved(): void {
    $resolvers = [
      $this->createEntityResolverMock(),
      $this->createEntityResolverMock(10),
    ];

    $resolver = new ChainEntityResolver($resolvers);

    $this->assertSame(10, $resolver->resolve($this->testNormalizer, $this->testData, $this->testEntityType));
  }

  /**
   * Tests the resolve method where one resolver returns 0.
   *
   * @covers ::__construct
   * @covers ::resolve
   */
  public function testResolverWithResolvedToZero(): void {
    $resolvers = [
      $this->createEntityResolverMock(0),
      $this->createEntityResolverMock(NULL, FALSE),
    ];

    $resolver = new ChainEntityResolver($resolvers);

    $this->assertSame(0, $resolver->resolve($this->testNormalizer, $this->testData, $this->testEntityType));
  }

  /**
   * Creates a mock entity resolver.
   *
   * @param null|int $return
   *   Whether the mocked resolve method should return TRUE or FALSE.
   * @param bool $called
   *   Whether or not the resolve method is expected to be called.
   *
   * @return \Drupal\serialization\EntityResolver\EntityResolverInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked entity resolver.
   */
  protected function createEntityResolverMock($return = NULL, $called = TRUE) {
    $mock = $this->createMock('Drupal\serialization\EntityResolver\EntityResolverInterface');

    if ($called) {
      $mock->expects($this->once())
        ->method('resolve')
        ->with($this->testNormalizer, $this->testData, $this->testEntityType)
        ->willReturn($return);
    }
    else {
      $mock->expects($this->never())
        ->method('resolve');
    }

    return $mock;
  }

}
