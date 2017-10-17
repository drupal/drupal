<?php

namespace Drupal\Tests\Component\Annotation;

use Drupal\Component\Annotation\AnnotationBase;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Annotation\AnnotationBase
 * @group Annotation
 */
class AnnotationBaseTest extends TestCase {

  /**
   * @covers ::getProvider
   * @covers ::setProvider
   */
  public function testSetProvider() {
    $plugin = new AnnotationBaseStub();
    $plugin->setProvider('example');
    $this->assertEquals('example', $plugin->getProvider());
  }

  /**
   * @covers ::getId
   */
  public function testGetId() {
    $plugin = new AnnotationBaseStub();
    // Doctrine sets the public prop directly.
    $plugin->id = 'example';
    $this->assertEquals('example', $plugin->getId());
  }

  /**
   * @covers ::getClass
   * @covers ::setClass
   */
  public function testSetClass() {
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
