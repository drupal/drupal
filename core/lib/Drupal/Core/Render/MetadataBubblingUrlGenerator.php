<?php

namespace Drupal\Core\Render;

use Drupal\Core\GeneratedUrl;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext as SymfonyRequestContext;

/**
 * Decorator for the URL generator, which bubbles bubbleable URL metadata.
 *
 * Implements a decorator for the URL generator that allows to automatically
 * collect and bubble up bubbleable metadata associated with URLs due to
 * outbound path and route processing. This approach helps keeping the render
 * and the routing subsystems decoupled.
 *
 * @see \Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface
 * @see \Drupal\Core\PathProcessor\OutboundPathProcessorInterface
 * @see \Drupal\Core\Render\BubbleableMetadata
 */
class MetadataBubblingUrlGenerator implements UrlGeneratorInterface {

  /**
   * The non-bubbling URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new bubbling URL generator service.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The non-bubbling URL generator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(UrlGeneratorInterface $url_generator, RendererInterface $renderer) {
    $this->urlGenerator = $url_generator;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function setContext(SymfonyRequestContext $context): void {
    $this->urlGenerator->setContext($context);
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(): SymfonyRequestContext {
    return $this->urlGenerator->getContext();
  }

  /**
   * {@inheritdoc}
   */
  public function getPathFromRoute(string $name, array $parameters = []) {
    return $this->urlGenerator->getPathFromRoute($name, $parameters);
  }

  /**
   * Bubbles the bubbleable metadata to the current render context.
   *
   * @param \Drupal\Core\GeneratedUrl $generated_url
   *   The generated URL whose bubbleable metadata to bubble.
   * @param array $options
   *   (optional) The URL options. Defaults to none.
   */
  protected function bubble(GeneratedUrl $generated_url, array $options = []) {
    // Bubbling metadata makes sense only if the code is executed inside a
    // render context. All code running outside controllers has no render
    // context by default, so URLs used there are not supposed to affect the
    // response cacheability.
    if ($this->renderer->hasRenderContext()) {
      $build = [];
      $generated_url->applyTo($build);
      $this->renderer->render($build);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generate(string $name, array $parameters = [], bool|int $referenceType = self::ABSOLUTE_PATH): string {
    $options['absolute'] = is_bool($referenceType) ? $referenceType : $referenceType === self::ABSOLUTE_URL;
    $generated_url = $this->generateFromRoute($name, $parameters, $options, TRUE);
    $this->bubble($generated_url);
    return $generated_url->getGeneratedUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function generateFromRoute(string $name, array $parameters = [], array $options = [], bool $collect_bubbleable_metadata = FALSE) {
    $generated_url = $this->urlGenerator->generateFromRoute($name, $parameters, $options, TRUE);
    if (!$collect_bubbleable_metadata) {
      $this->bubble($generated_url, $options);
    }
    return $collect_bubbleable_metadata ? $generated_url : $generated_url->getGeneratedUrl();
  }

}
