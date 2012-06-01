<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\LegacyRequestSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * KernelEvents::REQUEST event subscriber to initialize theme and modules.
 *
 * @todo Remove this subscriber when all of the code in it has been refactored.
 */
class LegacyRequestSubscriber implements EventSubscriberInterface {

  /**
   * Initializes the rest of the legacy Drupal subsystems.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestLegacy(GetResponseEvent $event) {
    if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
      // Prior to invoking hook_init(), initialize the theme (potentially a
      // custom one for this page), so that:
      // - Modules with hook_init() implementations that call theme() or
      //   theme_get_registry() don't initialize the incorrect theme.
      // - The theme can have hook_*_alter() implementations affect page
      //   building (e.g., hook_form_alter(), hook_node_view_alter(),
      //   hook_page_alter()), ahead of when rendering starts.
      menu_set_custom_theme();
      drupal_theme_initialize();
      module_invoke_all('init');

      // Tell Drupal it is now fully bootstrapped (for the benefit of code that
      // calls drupal_get_bootstrap_phase()), but without having
      // _drupal_bootstrap_full() do anything, since we've already done the
      // equivalent above and in earlier listeners.
      _drupal_bootstrap_full(TRUE);
      drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestLegacy', 90);

    return $events;
  }
}
