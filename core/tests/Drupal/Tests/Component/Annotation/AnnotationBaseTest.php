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
   * @legacy-covers ::getProvider
   * @legacy-covers ::setProvider
   */
  public function testSetProvider(): void {
    $plugin = new AnnotationBaseStub();
    $plugin->setProvider('example');
    $this->assertEquals('example', $plugin->getProvider());
  }

  /**
   * @legacy-covers ::getId
   */
  public function testGetId(): void {
    $plugin = new AnnotationBaseStub();
    // Doctrine sets the public prop directly.
    $plugin->id = 'example';
    $this->assertEquals('example', $plugin->getId());
  }

  /**
   * @legacy-covers ::getClass
   * @legacy-covers ::setClass
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
  public function get() {}

}
