<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation;

use Drupal\Component\Annotation\AnnotationBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Component\Annotation\AnnotationBase.
 */
#[CoversClass(AnnotationBase::class)]
#[Group('Annotation')]
class AnnotationBaseTest extends TestCase {

  /**
   * @see ::getProvider
   * @see ::setProvider
   */
  public function testSetProvider(): void {
    $plugin = new AnnotationBaseStub();
    $plugin->setProvider('example');
    $this->assertEquals('example', $plugin->getProvider());
  }

  /**
   * Tests get id.
   */
  public function testGetId(): void {
    $plugin = new AnnotationBaseStub();
    // Doctrine sets the public prop directly.
    $plugin->id = 'example';
    $this->assertEquals('example', $plugin->getId());
  }

  /**
   * @see ::getClass
   * @see ::setClass
   */
  public function testSetClass(): void {
    $plugin = new AnnotationBaseStub();
    $plugin->setClass('example');
    $this->assertEquals('example', $plugin->getClass());
  }

}
/**
 * {@inheritdoc}
 */
class AnnotationBaseStub extends AnnotationBase {

  /**
   * {@inheritdoc}
   */
  public function get(): void {}

}
