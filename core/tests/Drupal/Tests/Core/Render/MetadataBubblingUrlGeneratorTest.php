<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\Core\Url;
use Drupal\Tests\Core\Routing\UrlGeneratorTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Confirm that the MetadataBubblingUrlGenerator is functioning properly.
 */
#[CoversClass(MetadataBubblingUrlGenerator::class)]
#[Group('Render')]
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
   * @legacy-covers ::bubble
   */
  #[DataProvider('providerUrlBubbleableMetadataBubbling')]
  public function testUrlBubbleableMetadataBubbling($collect_bubbleable_metadata, $invocations, array $options): void {
    $this->renderer->expects($this->exactly($invocations))
      ->method('render')
      ->willReturnCallback(function ($build): void {
        $this->assertArrayHasKey('#cache', $build);
      });

    $url = new Url('test_1', [], $options);
    $url->setUrlGenerator($this->generator);
    $url->toString($collect_bubbleable_metadata);
  }

  /**
   * Data provider for ::testUrlBubbleableMetadataBubbling().
   */
  public static function providerUrlBubbleableMetadataBubbling(): array {
    return [
      // No bubbling when bubbleable metadata is collected.
      [TRUE, 0, []],
      // Bubbling when bubbleable metadata is not collected.
      [FALSE, 1, []],
    ];
  }

}
