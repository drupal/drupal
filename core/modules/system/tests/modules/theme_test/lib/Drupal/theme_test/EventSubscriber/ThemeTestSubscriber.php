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
      $GLOBALS['theme_test_output'] = theme('more_link', array('url' => 'user', 'title' => 'Themed output generated in a KernelEvents::REQUEST listener'));
    }
    if (strpos($current_path, 'user/autocomplete') === 0) {
      // Register a fake registry loading callback. If it gets called by
      // theme_get_registry(), the registry has not been initialized yet.
      _theme_registry_callback('_theme_test_load_registry', array());
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onRequest');
    return $events;
  }

}
