<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin\Attribute;

use Drupal\Component\Plugin\Attribute\AttributeBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Component\Plugin\Attribute\AttributeBase.
 */
#[CoversClass(AttributeBase::class)]
#[Group('Attribute')]
class AttributeBaseTest extends TestCase {

  /**
   * @see ::getProvider
   * @see ::setProvider
   */
  public function testSetProvider(): void {
    $plugin = new AttributeBaseStub(id: '1');
    $plugin->setProvider('example');
    $this->assertEquals('example', $plugin->getProvider());
  }

  /**
   * Tests get id.
   */
  public function testGetId(): void {
    $plugin = new AttributeBaseStub(id: 'example');
    $this->assertEquals('example', $plugin->getId());
  }

  /**
   * @see ::getClass
   * @see ::setClass
   */
  public function testSetClass(): void {
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
