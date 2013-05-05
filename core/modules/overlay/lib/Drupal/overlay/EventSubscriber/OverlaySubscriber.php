<?php

/**
 * @file
 * Contains Drupal\overlay\EventSubscriber\OverlaySubscriber.
 */

namespace Drupal\overlay\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Overlay subscriber for controller requests.
 */
class OverlaySubscriber implements EventSubscriberInterface {

  /**
   * Performs end of request tasks.
   *
   * When viewing an overlay child page, check if we need to trigger a refresh of
   * the supplemental regions of the overlay on the next page request.
   */
  public function onResponse(FilterResponseEvent $event) {
    // Check that we are in an overlay child page.
    if (overlay_get_mode() == 'child') {
      // Load any markup that was stored earlier in the page request, via calls
      // to overlay_store_rendered_content(). If none was stored, this is not a
      // page request where we expect any changes to the overlay supplemental
      // regions to have occurred, so we do not need to proceed any further.
      $original_markup = overlay_get_rendered_content();
      if (!empty($original_markup)) {
        // Compare the original markup to the current markup that we get from
        // rendering each overlay supplemental region now. If they don't match,
        // something must have changed, so we request a refresh of that region
        // within the parent window on the next page request.
        foreach (overlay_supplemental_regions() as $region) {
          if (!isset($original_markup[$region]) || $original_markup[$region] != overlay_render_region($region)) {
            overlay_request_refresh($region);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onResponse');

    return $events;
  }
}
