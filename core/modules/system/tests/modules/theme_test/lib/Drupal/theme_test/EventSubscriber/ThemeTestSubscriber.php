<?php

/**
 * @file
 * Contains \Drupal\theme_test\EventSubscriber\ThemeTestSubscriber.
 */

namespace Drupal\theme_test\EventSubscriber;

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
   * Generates themed output early in a page request.
   *
   * @see \Drupal\system\Tests\Theme\ThemeEarlyInitializationTest::testRequestListener()
   */
  public function onRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    $current_path = $request->attributes->get('_system_path');
    if ($current_path == 'theme-test/request-listener') {
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
        '#theme' => 'more_link',
        '#url' => 'user',
        '#title' => 'Themed output generated in a KernelEvents::REQUEST listener',
      );
      $GLOBALS['theme_test_output'] = drupal_render($more_link);
    }
  }

  /**
   * Ensures that the theme registry was not initialized.
   */
  public function onView(GetResponseEvent $event) {
    $request = $event->getRequest();
    $current_path = $request->attributes->get('_system_path');
    if (strpos($current_path, 'user/autocomplete') === 0) {
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
