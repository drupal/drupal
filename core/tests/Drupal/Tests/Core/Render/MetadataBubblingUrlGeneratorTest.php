<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\RouteProcessor\RouteProcessorManager;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Tests\Core\Routing\UrlGeneratorTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Confirm that the MetadataBubblingUrlGenerator is functioning properly.
 */
#[CoversClass(MetadataBubblingUrlGenerator::class)]
#[Group('Render')]
class MetadataBubblingUrlGeneratorTest extends UrlGeneratorTest {

  /**
   * The renderer.
   */
  protected RendererInterface&Stub $renderer;

  /**
   * The URL generator.
   */
  protected UrlGeneratorInterface $urlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->urlGenerator = $this->generator;

    $this->renderer = $this->createStub(RendererInterface::class);
    $this->renderer
      ->method('hasRenderContext')
      ->willReturn(TRUE);

    $this->generator = new MetadataBubblingUrlGenerator($this->urlGenerator, $this->renderer);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpMockRouteProcessorManager(): void {
    $this->routeProcessorManager = $this->createMock(RouteProcessorManager::class);
    $reflection = new \ReflectionProperty($this->urlGenerator, 'routeProcessor');
    $reflection->setValue($this->urlGenerator, $this->routeProcessorManager);
  }

  /**
   * Initializes the $renderer as a mock object.
   */
  protected function setUpMockRenderer(): void {
    $this->renderer = $this->createMock(RendererInterface::class);
    $this->renderer
      ->method('hasRenderContext')
      ->willReturn(TRUE);
    $reflection = new \ReflectionProperty($this->generator, 'renderer');
    $reflection->setValue($this->generator, $this->renderer);
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
  public function testUrlBubbleableMetadataBubbling(bool $collect_bubbleable_metadata, int $invocations, array $options): void {
    $this->setUpMockRenderer();

    $this->renderer->expects($this->exactly($invocations))
      ->method('render')
      ->willReturnCallback(function (array|\ArrayAccess $build): void {
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
