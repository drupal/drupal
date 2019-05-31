<?php

namespace Drupal\Tests\serialization\Unit\EntityResolver;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\serialization\EntityResolver\UuidResolver;

/**
 * @coversDefaultClass \Drupal\serialization\EntityResolver\UuidResolver
 * @group serialization
 */
class UuidResolverTest extends UnitTestCase {

  /**
   * The UuidResolver instance.
   *
   * @var \Drupal\serialization\EntityResolver\UuidResolver
   */
  protected $resolver;

  /**
   * The mock entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);

    $this->resolver = new UuidResolver($this->entityRepository);
  }

  /**
   * Test resolve() with a class using the incorrect interface.
   */
  public function testResolveNotInInterface() {
    $this->entityRepository->expects($this->never())
      ->method('loadEntityByUuid');

    $normalizer = $this->createMock('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    $this->assertNull($this->resolver->resolve($normalizer, [], 'test_type'));
  }

  /**
   * Test resolve() with a class using the correct interface but no UUID.
   */
  public function testResolveNoUuid() {
    $this->entityRepository->expects($this->never())
      ->method('loadEntityByUuid');

    $normalizer = $this->createMock('Drupal\serialization\EntityResolver\UuidReferenceInterface');
    $normalizer->expects($this->once())
      ->method('getUuid')
      ->with([])
      ->will($this->returnValue(NULL));
    $this->assertNull($this->resolver->resolve($normalizer, [], 'test_type'));
  }

  /**
   * Test resolve() with correct interface but no matching entity for the UUID.
   */
  public function testResolveNoEntity() {
    $uuid = '392eab92-35c2-4625-872d-a9dab4da008e';

    $this->entityRepository->expects($this->once())
      ->method('loadEntityByUuid')
      ->with('test_type')
      ->will($this->returnValue(NULL));

    $normalizer = $this->createMock('Drupal\serialization\EntityResolver\UuidReferenceInterface');
    $normalizer->expects($this->once())
      ->method('getUuid')
      ->with([])
      ->will($this->returnValue($uuid));

    $this->assertNull($this->resolver->resolve($normalizer, [], 'test_type'));
  }

  /**
   * Test resolve() when a UUID corresponds to an entity.
   */
  public function testResolveWithEntity() {
    $uuid = '392eab92-35c2-4625-872d-a9dab4da008e';

    $entity = $this->createMock('Drupal\Core\Entity\EntityInterface');
    $entity->expects($this->once())
      ->method('id')
      ->will($this->returnValue(1));

    $this->entityRepository->expects($this->once())
      ->method('loadEntityByUuid')
      ->with('test_type', $uuid)
      ->will($this->returnValue($entity));

    $normalizer = $this->createMock('Drupal\serialization\EntityResolver\UuidReferenceInterface');
    $normalizer->expects($this->once())
      ->method('getUuid')
      ->with([])
      ->will($this->returnValue($uuid));
    $this->assertSame(1, $this->resolver->resolve($normalizer, [], 'test_type'));
  }

}
