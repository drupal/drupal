<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityDisplayBase
 *
 * @group Config
 */
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
   * @covers ::getTargetEntityTypeId
   */
  public function testGetTargetEntityTypeId(): void {
    $reflection = new \ReflectionProperty($this->entityDisplay, 'targetEntityType');
    $reflection->setValue($this->entityDisplay, 'test');
    $this->assertEquals('test', $this->entityDisplay->getTargetEntityTypeId());
  }

  /**
   * @covers ::getMode
   */
  public function testGetMode(): void {
    $reflection = new \ReflectionProperty($this->entityDisplay, 'mode');
    $reflection->setValue($this->entityDisplay, 'test');
    $this->assertEquals('test', $this->entityDisplay->getMode());
  }

  /**
   * @covers ::getOriginalMode
   */
  public function testGetOriginalMode(): void {
    $reflection = new \ReflectionProperty($this->entityDisplay, 'originalMode');
    $reflection->setValue($this->entityDisplay, 'test');
    $this->assertEquals('test', $this->entityDisplay->getOriginalMode());
  }

  /**
   * @covers ::getTargetBundle
   */
  public function testGetTargetBundle(): void {
    $reflection = new \ReflectionProperty($this->entityDisplay, 'bundle');
    $reflection->setValue($this->entityDisplay, 'test');
    $this->assertEquals('test', $this->entityDisplay->getTargetBundle());
  }

  /**
   * @covers ::setTargetBundle
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

  public function getPluginCollections() {
    return [];
  }

  public function getRenderer($field_name) {
    return NULL;
  }

}
