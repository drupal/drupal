<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation;

use Drupal\Component\Annotation\Plugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Component\Annotation\Plugin.
 */
#[CoversClass(Plugin::class)]
#[Group('Annotation')]
class PluginTest extends TestCase {

  /**
   * @legacy-covers ::__construct
   * @legacy-covers ::parse
   * @legacy-covers ::get
   */
  public function testGet(): void {
    // Assert all values are accepted through constructor and default value is
    // used for non existent but defined property.
    $plugin = new PluginStub([
      1 => 'oak',
      'foo' => 'bar',
      'biz' => [
        'baz' => 'boom',
      ],
      'nestedAnnotation' => new Plugin([
        'foo' => 'bar',
      ]),
    ]);
    $this->assertEquals([
      // This property wasn't in our definition but is defined as a property on
      // our plugin class.
      'defaultProperty' => 'test_value',
      1 => 'oak',
      'foo' => 'bar',
      'biz' => [
        'baz' => 'boom',
      ],
      'nestedAnnotation' => [
        'foo' => 'bar',
      ],
    ], $plugin->get());

    // Without default properties, we get a completely empty plugin definition.
    $plugin = new Plugin([]);
    $this->assertEquals([], $plugin->get());
  }

  /**
   * @legacy-covers ::getProvider
   */
  public function testGetProvider(): void {
    $plugin = new Plugin(['provider' => 'example']);
    $this->assertEquals('example', $plugin->getProvider());
  }

  /**
   * @legacy-covers ::setProvider
   */
  public function testSetProvider(): void {
    $plugin = new Plugin([]);
    $plugin->setProvider('example');
    $this->assertEquals('example', $plugin->getProvider());
  }

  /**
   * @legacy-covers ::getId
   */
  public function testGetId(): void {
    $plugin = new Plugin(['id' => 'example']);
    $this->assertEquals('example', $plugin->getId());
  }

  /**
   * @legacy-covers ::getClass
   */
  public function testGetClass(): void {
    $plugin = new Plugin(['class' => 'example']);
    $this->assertEquals('example', $plugin->getClass());
  }

  /**
   * @legacy-covers ::setClass
   */
  public function testSetClass(): void {
    $plugin = new Plugin([]);
    $plugin->setClass('example');
    $this->assertEquals('example', $plugin->getClass());
  }

}
/**
 * {@inheritdoc}
 */
class PluginStub extends Plugin {

  /**
   * A default property for testing.
   *
   * @var string
   */
  protected $defaultProperty = 'test_value';

}
