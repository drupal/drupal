<?php

declare(strict_types=1);

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
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->renderer = $this->createMock('\Drupal\Core\Render\RendererInterface');
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
      ->willReturnCallback(function ($build) {
        $this->assertArrayHasKey('#cache', $build);
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
