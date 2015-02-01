<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\EntityDisplayBaseTest.
 */

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity\EntityDisplayBase
 *
 * @group Config
 */
class EntityDisplayBaseTest extends UnitTestCase {

  /**
   * @covers ::getTargetEntityTypeId()
   */
  public function testGetTargetEntityTypeId() {
    $mock = $this->getMockForAbstractClass('\Drupal\Core\Entity\EntityDisplayBase', [], '', FALSE);
    $reflection = new \ReflectionProperty($mock, 'targetEntityType');
    $reflection->setAccessible(TRUE);
    $reflection->setValue($mock, 'test');
    $this->assertEquals('test', $mock->getTargetEntityTypeId());
  }

  /**
   * @covers ::getMode()
   */
  public function testGetMode() {
    $mock = $this->getMockForAbstractClass('\Drupal\Core\Entity\EntityDisplayBase', [], '', FALSE);
    $reflection = new \ReflectionProperty($mock, 'mode');
    $reflection->setAccessible(TRUE);
    $reflection->setValue($mock, 'test');
    $this->assertEquals('test', $mock->getMode());
  }

  /**
   * @covers ::getOriginalMode()
   */
  public function testGetOriginalMode() {
    $mock = $this->getMockForAbstractClass('\Drupal\Core\Entity\EntityDisplayBase', [], '', FALSE);
    $reflection = new \ReflectionProperty($mock, 'originalMode');
    $reflection->setAccessible(TRUE);
    $reflection->setValue($mock, 'test');
    $this->assertEquals('test', $mock->getOriginalMode());
  }

  /**
   * @covers ::getDisplayBundle()
   */
  public function testGetDisplayBundle() {
    $mock = $this->getMockForAbstractClass('\Drupal\Core\Entity\EntityDisplayBase', [], '', FALSE);
    $reflection = new \ReflectionProperty($mock, 'bundle');
    $reflection->setAccessible(TRUE);
    $reflection->setValue($mock, 'test');
    $this->assertEquals('test', $mock->getTargetBundle());
  }

  /**
   * @covers ::setDisplayBundle()
   */
  public function testSetDisplayBundle() {
    $mock = $this->getMockForAbstractClass('\Drupal\Core\Entity\EntityDisplayBase', [], '', FALSE);
    $reflection = new \ReflectionProperty($mock, 'bundle');
    $reflection->setAccessible(TRUE);
    $mock->setTargetBundle('test');
    $this->assertEquals('test', $reflection->getValue($mock));
  }

}
