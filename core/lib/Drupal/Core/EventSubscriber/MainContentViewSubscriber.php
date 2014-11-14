<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\MainContentViewSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * View subscriber rendering main content render arrays into responses.
 *
 * Additional target rendering formats can be defined by adding another service
 * that implements \Drupal\Core\Render\MainContent\MainContentRendererInterface
 * and tagging it as a @code render.main_content_renderer @endcode, then
 * \Drupal\Core\Render\MainContent\MainContentRenderersPass will detect it and
 * use it when appropriate.
 *
 * @see \Drupal\Core\Render\MainContent\MainContentRendererInterface
 * @see \Drupal\Core\Render\MainContentControllerPass
 */
class MainContentViewSubscriber implements EventSubscriberInterface {

  /**
   * The class resolver service.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $classResolver;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The available main content renderer services, keyed per format.
   *
   * @var array
   */
  protected $mainContentRenderers;

  /**
   * Constructs a new MainContentViewSubscriber object.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param array $main_content_renderers
   *   The available main content renderer service IDs, keyed by format.
   */
  public function __construct(ClassResolverInterface $class_resolver, RouteMatchInterface $route_match, array $main_content_renderers) {
    $this->classResolver = $class_resolver;
    $this->routeMatch = $route_match;
    $this->mainContentRenderers = $main_content_renderers;
  }

  /**
   * Sets a response given a (main content) render array.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent $event
   *   The event to process.
   */
  public function onViewRenderArray(GetResponseForControllerResultEvent $event) {
    $request = $event->getRequest();
    $result = $event->getControllerResult();

    $format = $request->getRequestFormat();

    // Render the controller result into a response if it's a render array.
    if (is_array($result)) {
      if (isset($this->mainContentRenderers[$format])) {
        $renderer = $this->classResolver->getInstanceFromDefinition($this->mainContentRenderers[$format]);
        $event->setResponse($renderer->renderResponse($result, $request, $this->routeMatch));
      }
      else {
        $supported_formats = array_keys($this->mainContentRenderers);
        $supported_mimetypes = array_map([$request, 'getMimeType'], $supported_formats);
        $event->setResponse(new JsonResponse([
          'message' => 'Not Acceptable.',
          'supported_mime_types' => $supported_mimetypes,
        ], 406));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = ['onViewRenderArray'];

    return $events;
  }

}
