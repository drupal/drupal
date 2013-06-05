<?php

/**
 * @file
 * Contains \Drupal\overlay\EventSubscriber\OverlaySubscriber.
 */

namespace Drupal\overlay\EventSubscriber;

use Drupal\Core\ContentNegotiation;
use Drupal\user\UserData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Overlay subscriber for controller requests.
 */
class OverlaySubscriber implements EventSubscriberInterface {

  /**
   * The content negotiation service.
   *
   * @var \Drupal\Core\ContentNegotiation
   */
  protected $negotiation;

  /**
   * The user.data service.
   *
   * @var \Drupal\user\UserData
   */
  protected $userData;

  /**
   * Constructs an OverlaySubscriber object.
   *
   * @param \Drupal\Core\ContentNegotiation $negotiation
   *   The content negotiation service.
   * @param \Drupal\user\UserData $user_data
   *   The user.data service.
   */
  public function __construct(ContentNegotiation $negotiation, UserData $user_data) {
    $this->negotiation = $negotiation;
    $this->userData = $user_data;
  }

  /**
   * Performs check on the beginning of a request.
   *
   * Determine whether the current page request is destined to appear in the
   * parent window or in the overlay window, and format the page accordingly.
   *
   * @see overlay_set_mode()
   */
  public function onRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    if ($this->negotiation->getContentType($request) != 'html') {
      // Only act on html pages.
      return;
    }
    global $user;

    $mode = overlay_get_mode();

    // Only act if the user has access to the overlay and a mode was not already
    // set. Other modules can also enable the overlay directly for other uses.
    $user_data = $this->userData->get('overlay', $user->uid, 'enabled');
    $use_overlay = !isset($user_data) || $user_data;
    if (empty($mode) && user_access('access overlay') && $use_overlay) {
      $current_path = $request->attributes->get('system_path');
      // After overlay is enabled on the modules page, redirect to
      // <front>#overlay=admin/modules to actually enable the overlay.
      if (isset($_SESSION['overlay_enable_redirect']) && $_SESSION['overlay_enable_redirect']) {
        unset($_SESSION['overlay_enable_redirect']);
        $response = new RedirectResponse(url('<front>', array('fragment' => 'overlay=' . $current_path, 'absolute' => TRUE)));
        $event->setResponse($response);
      }

      if ($request->query->get('render') == 'overlay') {
        // If a previous page requested that we close the overlay, close it and
        // redirect to the final destination.
        if (isset($_SESSION['overlay_close_dialog'])) {
          call_user_func_array('overlay_close_dialog', $_SESSION['overlay_close_dialog']);
          unset($_SESSION['overlay_close_dialog']);
        }
        // If this page shouldn't be rendered inside the overlay, redirect to
        // the parent.
        elseif (!path_is_admin($current_path)) {
          overlay_close_dialog($current_path, array('query' => drupal_get_query_parameters(NULL, array('render'))));
        }

        // Indicate that we are viewing an overlay child page.
        overlay_set_mode('child');

        // Unset the render parameter to avoid it being included in URLs on the
        // page.
        $request->query->remove('render');
      }
      // Do not enable the overlay if we already are on an admin page.
      elseif (!path_is_admin($current_path)) {
        // Otherwise add overlay parent code and our behavior.
        overlay_set_mode('parent');
      }
    }
  }

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
    $events[KernelEvents::REQUEST][] = array('onRequest');
    $events[KernelEvents::RESPONSE][] = array('onResponse');

    return $events;
  }
}
