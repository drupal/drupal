<?php

declare(strict_types=1);

namespace Drupal\theme_test\EventSubscriber;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Theme test subscriber for controller requests.
 */
class ThemeTestSubscriber implements EventSubscriberInterface {

  /**
   * The used container.
   *
   * @todo This variable is never initialized, so we don't know what it is.
   *   See https://www.drupal.org/node/2721315
   */
  protected $container;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new ThemeTestSubscriber.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The route match handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RouteMatchInterface $current_route_match, RendererInterface $renderer) {
    $this->currentRouteMatch = $current_route_match;
    $this->renderer = $renderer;
  }

  /**
   * Generates themed output early in a page request.
   *
   * @see \Drupal\system\Tests\Theme\ThemeEarlyInitializationTest::testRequestListener()
   */
  public function onRequest(RequestEvent $event) {
    if ($this->currentRouteMatch->getRouteName() === 'theme_test.request_listener') {
      // First, force the theme registry to be rebuilt on this page request.
      // This allows us to test a full initialization of the theme system in
      // the code below.
      \Drupal::service('theme.registry')->reset();
      // Next, initialize the theme system by storing themed text in a global
      // variable. We will use this later in
      // theme_test_request_listener_page_callback() to test that even when the
      // theme system is initialized this early, it is still capable of
      // returning output and theming the page as a whole.
      $more_link = [
        '#type' => 'more_link',
        '#url' => Url::fromRoute('user.page'),
        '#attributes' => ['title' => 'Themed output generated in a KernelEvents::REQUEST listener'],
      ];
      $GLOBALS['theme_test_output'] = $this->renderer->renderInIsolation($more_link);
    }
  }

  /**
   * Ensures that the theme registry was not initialized.
   */
  public function onView(RequestEvent $event) {
    $current_route = $this->currentRouteMatch->getRouteName();
    $entity_autocomplete_route = [
      'system.entity_autocomplete',
    ];

    if (in_array($current_route, $entity_autocomplete_route)) {
      if ($this->container->initialized('theme.registry')) {
        throw new \Exception('registry initialized');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    $events[KernelEvents::VIEW][] = ['onView', -1000];
    return $events;
  }

}
