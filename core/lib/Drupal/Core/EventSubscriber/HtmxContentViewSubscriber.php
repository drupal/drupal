<?php

declare(strict_types=1);

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * View subscriber rendering main content from `_htmx_route` option routes.
 *
 * Uses HtmxRenderer to create an HTML response  for any route with the
 * `_htmx_route` option set to TRUE. This subscriber runs before the
 * MainContentViewSubscriber.
 *
 * @see \Drupal\Core\Render\MainContent\MainContentRendererInterface
 * @see \Drupal\Core\Render\MainContent\HtmxRenderer
 */
class HtmxContentViewSubscriber implements EventSubscriberInterface {

  public function __construct(
    #[AutowireServiceClosure('main_content_renderer.htmx')]
    protected \Closure $htmxRenderer,
    protected RouteMatchInterface $routeMatch,
  ) {}

  /**
   * Sets a minimal response render array on an `_htmx_route` route.
   *
   * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
   *   The event to process.
   */
  public function renderHtmxResponse(ViewEvent $event): void {
    $htmxRoute = $this->routeMatch->getRouteObject()->getOption('_htmx_route') ?? FALSE;
    $request = $event->getRequest();
    $result = $event->getControllerResult();

    if ($htmxRoute && is_array($result) && $request->getRequestFormat() === 'html') {
      $response = $this->getHtmxRenderer()->renderResponse($result, $request, $this->routeMatch);
      $event->setResponse($response);
    }
  }

  /**
   * Gets the HtmxRenderer service.
   *
   * @return \Drupal\Core\Render\MainContent\HtmxRenderer
   *   The service instantiated by the autowire closure.
   */
  protected function getHtmxRenderer(): MainContentRendererInterface {
    return ($this->htmxRenderer)();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // MainContentViewSubscriber has a default priority of 0.
    // Set a higher priority to ensure that this subscriber runs before it.
    $events[KernelEvents::VIEW][] = ['renderHtmxResponse', 100];
    return $events;
  }

}
