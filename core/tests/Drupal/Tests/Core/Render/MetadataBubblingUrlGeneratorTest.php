<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\MetadataBubblingUrlGeneratorTest.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\Core\Url;
use Drupal\Tests\Core\Routing\UrlGeneratorTest;

/**
 * Confirm that the MetadataBubblingUrlGenerator is functioning properly.
 *
 * @coversDefaultClass \Drupal\Core\Render\MetadataBubblingUrlGenerator
 *
 * @group Render
 */
class MetadataBubblingUrlGeneratorTest extends UrlGeneratorTest {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->renderer = $this->getMock('\Drupal\Core\Render\RendererInterface');
    $this->renderer->expects($this->any())
      ->method('hasRenderContext')
      ->willReturn(TRUE);

    $this->generator = new MetadataBubblingUrlGenerator($this->generator, $this->renderer);
  }

  /**
   * Tests bubbling of cacheable metadata for URLs.
   *
   * @param bool $collect_bubbleable_metadata
   *   Whether bubbleable metadata should be collected.
   * @param int $invocations
   *   The expected amount of invocations for the ::bubble() method.
   * @param array $options
   *   The URL options.
   *
   * @covers ::bubble
   *
   * @dataProvider providerUrlBubbleableMetadataBubbling
   */
  public function testUrlBubbleableMetadataBubbling($collect_bubbleable_metadata, $invocations, array $options) {
    $self = $this;

    $this->renderer->expects($this->exactly($invocations))
      ->method('render')
      ->willReturnCallback(function ($build) use ($self) {
        $self->assertTrue(!empty($build['#cache']));
      });

    $url = new Url('test_1', [], $options);
    $url->setUrlGenerator($this->generator);
    $url->toString($collect_bubbleable_metadata);
  }

  /**
   * Data provider for ::testUrlBubbleableMetadataBubbling().
   */
  public function providerUrlBubbleableMetadataBubbling() {
    return [
      // No bubbling when bubbleable metadata is collected.
      [TRUE, 0, []],
      // Bubbling when bubbleable metadata is not collected.
      [FALSE, 1, []],
    ];
  }

}
