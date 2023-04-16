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
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);

    $this->resolver = new UuidResolver($this->entityRepository);
  }

  /**
   * Tests resolve() with a class using the incorrect interface.
   */
  public function testResolveNotInInterface() {
    $this->entityRepository->expects($this->never())
      ->method('loadEntityByUuid');

    $normalizer = $this->createMock('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    $this->assertNull($this->resolver->resolve($normalizer, [], 'test_type'));
  }

  /**
   * Tests resolve() with a class using the correct interface but no UUID.
   */
  public function testResolveNoUuid() {
    $this->entityRepository->expects($this->never())
      ->method('loadEntityByUuid');

    $normalizer = $this->createMock('Drupal\serialization\EntityResolver\UuidReferenceInterface');
    $normalizer->expects($this->once())
      ->method('getUuid')
      ->with([])
      ->willReturn(NULL);
    $this->assertNull($this->resolver->resolve($normalizer, [], 'test_type'));
  }

  /**
   * Tests resolve() with correct interface but no matching entity for the UUID.
   */
  public function testResolveNoEntity() {
    $uuid = '392eab92-35c2-4625-872d-a9dab4da008e';

    $this->entityRepository->expects($this->once())
      ->method('loadEntityByUuid')
      ->with('test_type')
      ->willReturn(NULL);

    $normalizer = $this->createMock('Drupal\serialization\EntityResolver\UuidReferenceInterface');
    $normalizer->expects($this->once())
      ->method('getUuid')
      ->with([])
      ->willReturn($uuid);

    $this->assertNull($this->resolver->resolve($normalizer, [], 'test_type'));
  }

  /**
   * Tests resolve() when a UUID corresponds to an entity.
   */
  public function testResolveWithEntity() {
    $uuid = '392eab92-35c2-4625-872d-a9dab4da008e';

    $entity = $this->createMock('Drupal\Core\Entity\EntityInterface');
    $entity->expects($this->once())
      ->method('id')
      ->willReturn(1);

    $this->entityRepository->expects($this->once())
      ->method('loadEntityByUuid')
      ->with('test_type', $uuid)
      ->willReturn($entity);

    $normalizer = $this->createMock('Drupal\serialization\EntityResolver\UuidReferenceInterface');
    $normalizer->expects($this->once())
      ->method('getUuid')
      ->with([])
      ->willReturn($uuid);
    $this->assertSame(1, $this->resolver->resolve($normalizer, [], 'test_type'));
  }

}
