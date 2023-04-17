<?php

declare(strict_types=1);

namespace Drupal\Tests\sdc\Traits;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Defines a trait for rendering components.
 *
 * @internal
 */
trait ComponentRendererTrait {

  /**
   * Renders a component for testing sake.
   *
   * @param array $component
   *   Component render array.
   * @param \Drupal\Core\Render\BubbleableMetadata|null $metadata
   *   Bubble metadata.
   *
   * @return \Symfony\Component\DomCrawler\Crawler
   *   Crawler for introspecting the rendered component.
   */
  protected function renderComponentRenderArray(array $component, ?BubbleableMetadata $metadata = NULL): Crawler {
    $component = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'sdc-wrapper',
      ],
      'component' => $component,
    ];
    $metadata = $metadata ?: new BubbleableMetadata();
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $output = $renderer->executeInRenderContext($context, fn () => $renderer->render($component));
    if (!$context->isEmpty()) {
      $metadata->addCacheableDependency($context->pop());
    }
    return new Crawler((string) $output);
  }

}
