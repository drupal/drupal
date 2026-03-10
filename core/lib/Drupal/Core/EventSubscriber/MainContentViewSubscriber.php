<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * View subscriber rendering main content render arrays into responses.
 *
 * Additional target rendering formats can be defined by adding another service
 * that implements \Drupal\Core\Render\MainContent\MainContentRendererInterface
 * and tagging it as a @code render.main_content_renderer @endcode.
 *
 * @see \Drupal\Core\Render\MainContent\MainContentRendererInterface
 * @see \Drupal\Core\Render\MainContentControllerPass
 */
class MainContentViewSubscriber implements EventSubscriberInterface {

  /**
   * URL query attribute to indicate the wrapper used to render a request.
   *
   * The wrapper format determines how the HTML is wrapped, for example in a
   * modal dialog.
   */
  const WRAPPER_FORMAT = '_wrapper_format';

  /**
   * Constructs a new MainContentViewSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Symfony\Component\DependencyInjection\ServiceLocator $renderers
   *   A service locator that contains the main content renderer services,
   *   keyed by the 'format' attribute.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    #[AutowireLocator('render.main_content_renderer', 'format')]
    protected ServiceLocator $renderers,
  ) {}

  /**
   * Sets a response given a (main content) render array.
   *
   * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
   *   The event to process.
   */
  public function onViewRenderArray(ViewEvent $event) {
    $request = $event->getRequest();
    $result = $event->getControllerResult();

    // Render the controller result into a response if it's a render array.
    if (is_array($result) && ($request->query->has(static::WRAPPER_FORMAT) || $request->getRequestFormat() == 'html')) {
      $wrapper = $request->query->get(static::WRAPPER_FORMAT, 'html');

      // Fall back to HTML if the requested wrapper envelope is not available.
      if (!$this->renderers->has($wrapper)) {
        $wrapper = 'html';
      }

      $renderer = $this->renderers->get($wrapper);
      $response = $renderer->renderResponse($result, $request, $this->routeMatch);
      // The main content render array is rendered into a different Response
      // object, depending on the specified wrapper format.
      if ($response instanceof CacheableResponseInterface) {
        $main_content_view_subscriber_cacheability = (new CacheableMetadata())->setCacheContexts(['url.query_args:' . static::WRAPPER_FORMAT]);
        $response->addCacheableDependency($main_content_view_subscriber_cacheability);
      }
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::VIEW][] = ['onViewRenderArray'];

    return $events;
  }

}
