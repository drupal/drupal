<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Htmx;

use Drupal\Core\Htmx\Htmx;
use Drupal\Core\Template\Attribute;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * Test all Htmx public utility methods.
 */
#[CoversClass(Htmx::class)]
#[Group('Htmx')]
class HtmxUtilitiesTest extends UnitTestCase {

  /**
   * Class under test.
   */
  protected Htmx $htmx;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Set up test values.

    $attributes = new Attribute(['apple' => 'orange', 'banana' => 'grape']);
    $headers = new HeaderBag(['pear' => 'kiwi', 'mango' => 'peach']);
    $this->htmx = new Htmx($attributes, $headers);
  }

  /**
   * Test ::hasHeader.
   */
  public function testHasHeader(): void {
    $this->assertTrue($this->htmx->hasHeader('pear'));
    $this->assertTrue($this->htmx->hasHeader('mango'));
    $this->assertFalse($this->htmx->hasHeader('kiwi'));
  }

  /**
   * Test ::hasAttribute.
   */
  public function testHasAttribute(): void {
    $this->assertTrue($this->htmx->hasAttribute('apple'));
    $this->assertTrue($this->htmx->hasAttribute('banana'));
    $this->assertFalse($this->htmx->hasAttribute('orange'));
  }

  /**
   * Test ::removeHeader.
   */
  public function testRemoveHeader(): void {
    $this->htmx->removeHeader('pear');
    $this->assertFalse($this->htmx->hasHeader('pear'));
  }

  /**
   * Test ::removeAttribute.
   */
  public function testRemoveAttribute(): void {
    $this->htmx->removeAttribute('apple');
    $this->assertFalse($this->htmx->hasAttribute('apple'));
  }

  /**
   * Test ::getAttributes.
   */
  public function testGetAttributes(): void {
    $attributes = $this->htmx->getAttributes();
    $this->assertEquals(2, count($attributes->storage()));
  }

  /**
   * Test ::getHeaders().
   */
  public function testGetHeaders(): void {
    $headers = $this->htmx->getHeaders();
    $this->assertEquals(2, $headers->count());
  }

  /**
   * Test ::applyTo with defaults.
   */
  public function testApplyTo(): void {
    $render = [
      '#attributes' => [
        'plum' => 'strawberry',
      ],
      '#attached' => [
        'http_header' => [
          ['melon', 'watermelon', TRUE],
        ],
      ],
    ];
    $this->htmx->applyTo($render);
    $this->assertTrue(isset($render['#attributes']));
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertTrue(isset($render['#attached']['library']));
    // We added 2 attributes and 2 headers.
    $this->assertEquals(3, count($render['#attributes']));
    $this->assertEquals(3, count($render['#attached']['http_header']));
    $this->assertEquals(['core/drupal.htmx'], $render['#attached']['library']);
  }

  /**
   * Test ::applyTo with attribute key.
   */
  public function testApplyToWithKey(): void {
    $render = [];
    $this->htmx->applyTo($render, '#wrapper_attributes');
    $this->assertTrue(isset($render['#wrapper_attributes']));
    $this->assertEquals(2, count($render['#wrapper_attributes']));
  }

  /**
   * Test ::createFromRenderArray.
   */
  public function testCreateFromRenderArray(): void {
    $source = [
      '#attributes' => [
        'data-hx-apple' => 'orange',
        'banana' => 'grape',
      ],
      '#attached' => [
        'http_header' => [
          ['pear', 'kiwi', TRUE],
          ['hx-mango', 'peach', TRUE],
        ],
      ],
      '#cache' => [
        'tags' => ['node:3', 'node:12'],
      ],
    ];
    $htmx = Htmx::createFromRenderArray($source);
    $this->assertTrue($htmx->hasAttribute('data-hx-apple'));
    $this->assertFalse($htmx->hasAttribute('banana'));
    $this->assertTrue($htmx->hasHeader('hx-mango'));
    $this->assertFalse($htmx->hasHeader('pear'));
    $render = [];
    $htmx->applyTo($render);
    $this->assertEquals(1, count($render['#attributes']));
    $this->assertEquals(1, count($render['#attached']['http_header']));
    $this->assertEquals(['node:3', 'node:12'], $render['#cache']['tags']);
  }

}
