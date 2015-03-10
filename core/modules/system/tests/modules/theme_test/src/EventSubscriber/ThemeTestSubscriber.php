<?php

/**
 * @file
 * Contains \Drupal\theme_test\EventSubscriber\ThemeTestSubscriber.
 */

namespace Drupal\theme_test\EventSubscriber;

use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Theme test subscriber for controller requests.
 */
class ThemeTestSubscriber implements EventSubscriberInterface {

  /**
   * The used container.
   *
   * @var \Symfony\Component\DependencyInjection\IntrospectableContainerInterface
   */
  protected $container;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new ThemeTestSubscriber.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   */
  public function __construct(RouteMatchInterface $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * Generates themed output early in a page request.
   *
   * @see \Drupal\system\Tests\Theme\ThemeEarlyInitializationTest::testRequestListener()
   */
  public function onRequest(GetResponseEvent $event) {
    if ($this->currentRouteMatch->getRouteName() === 'theme_test.request_listener') {
      // First, force the theme registry to be rebuilt on this page request.
      // This allows us to test a full initialization of the theme system in
      // the code below.
      drupal_theme_rebuild();
      // Next, initialize the theme system by storing themed text in a global
      // variable. We will use this later in
      // theme_test_request_listener_page_callback() to test that even when the
      // theme system is initialized this early, it is still capable of
      // returning output and theming the page as a whole.
      $more_link = array(
        '#type' => 'more_link',
        '#url' => Url::fromRoute('user.page'),
        '#attributes' => array('title' => 'Themed output generated in a KernelEvents::REQUEST listener'),
      );
      $GLOBALS['theme_test_output'] = drupal_render($more_link);
    }
  }

  /**
   * Ensures that the theme registry was not initialized.
   */
  public function onView(GetResponseEvent $event) {
    $current_route = $this->currentRouteMatch->getRouteName();
    $entity_autcomplete_route = array(
      'system.entity_autocomplete',
    );

    if (in_array($current_route, $entity_autcomplete_route)) {
      if ($this->container->initialized('theme.registry')) {
        throw new \Exception('registry initialized');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onRequest');
    $events[KernelEvents::VIEW][] = array('onView', -1000);
    return $events;
  }

}
