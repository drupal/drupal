<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Entity\EntityType;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests Drupal\Core\Entity\EntityDisplayBase.
 */
#[CoversClass(EntityDisplayBase::class)]
#[Group('Config')]
class EntityDisplayBaseTest extends UnitTestCase {

  /**
   * The mocked EntityDisplay object for testing.
   */
  protected EntityDisplayBaseMockableClass&MockObject $entityDisplay;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityDisplay = $this->getMockBuilder(EntityDisplayBaseMockableClass::class)
      ->disableOriginalConstructor()
      ->onlyMethods([])
      ->getMock();
  }

  /**
   * Tests get target entity type id.
   *
   * @legacy-covers ::getTargetEntityTypeId
   */
  public function testGetTargetEntityTypeId(): void {
    $reflection = new \ReflectionProperty($this->entityDisplay, 'targetEntityType');
    $reflection->setValue($this->entityDisplay, 'test');
    $this->assertEquals('test', $this->entityDisplay->getTargetEntityTypeId());
  }

  /**
   * Tests get mode.
   *
   * @legacy-covers ::getMode
   */
  public function testGetMode(): void {
    $reflection = new \ReflectionProperty($this->entityDisplay, 'mode');
    $reflection->setValue($this->entityDisplay, 'test');
    $this->assertEquals('test', $this->entityDisplay->getMode());
  }

  /**
   * Tests get original mode.
   *
   * @legacy-covers ::getOriginalMode
   */
  public function testGetOriginalMode(): void {
    $reflection = new \ReflectionProperty($this->entityDisplay, 'originalMode');
    $reflection->setValue($this->entityDisplay, 'test');
    $this->assertEquals('test', $this->entityDisplay->getOriginalMode());
  }

  /**
   * Tests get target bundle.
   *
   * @legacy-covers ::getTargetBundle
   */
  public function testGetTargetBundle(): void {
    $reflection = new \ReflectionProperty($this->entityDisplay, 'bundle');
    $reflection->setValue($this->entityDisplay, 'test');
    $this->assertEquals('test', $this->entityDisplay->getTargetBundle());
  }

  /**
   * Tests set target bundle.
   *
   * @legacy-covers ::setTargetBundle
   */
  public function testSetTargetBundle(): void {
    $reflection = new \ReflectionProperty($this->entityDisplay, 'bundle');
    $this->entityDisplay->setTargetBundle('test');
    $this->assertEquals('test', $reflection->getValue($this->entityDisplay));
  }

}

/**
 * A class extending EntityDisplayBase for testing purposes.
 */
class EntityDisplayBaseMockableClass extends EntityDisplayBase {

  public function getPluginCollections(): array {
    return [];
  }

  public function getRenderer($field_name): NULL {
    return NULL;
  }

  public function getEntityType(): EntityType {
    return new EntityType([
      'id' => 'entity_view_display',
      'entity_keys' => [
        'id' => 'id',
      ],
    ]);
  }

}
