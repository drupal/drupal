<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin\Attribute;

use Drupal\Component\Plugin\Attribute\AttributeBase;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Plugin\Attribute\AttributeBase
 * @group Attribute
 */
class AttributeBaseTest extends TestCase {

  /**
   * @covers ::getProvider
   * @covers ::setProvider
   */
  public function testSetProvider() {
    $plugin = new AttributeBaseStub(id: '1');
    $plugin->setProvider('example');
    $this->assertEquals('example', $plugin->getProvider());
  }

  /**
   * @covers ::getId
   */
  public function testGetId() {
    $plugin = new AttributeBaseStub(id: 'example');
    $this->assertEquals('example', $plugin->getId());
  }

  /**
   * @covers ::getClass
   * @covers ::setClass
   */
  public function testSetClass() {
    $plugin = new AttributeBaseStub(id: '1');
    $plugin->setClass('example');
    $this->assertEquals('example', $plugin->getClass());
  }

}
/**
 * {@inheritdoc}
 */
class AttributeBaseStub extends AttributeBase {

}
