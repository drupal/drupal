<?php

/**
 * @file
 * Contains \Drupal\overlay\EventSubscriber\OverlaySubscriber.
 */

namespace Drupal\overlay\EventSubscriber;

use Drupal\Core\ContentNegotiation;
use Drupal\Core\Routing\PathBasedGeneratorInterface;
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
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\PathBasedGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs an OverlaySubscriber object.
   *
   * @param \Drupal\Core\ContentNegotiation $negotiation
   *   The content negotiation service.
   * @param \Drupal\user\UserData $user_data
   *   The user.data service.
   * @param \Drupal\Core\Routing\PathBasedGeneratorInterface $url_generator
   *   The url generator service.
   */
  public function __construct(ContentNegotiation $negotiation, UserData $user_data, PathBasedGeneratorInterface $url_generator) {
    $this->negotiation = $negotiation;
    $this->userData = $user_data;
    $this->urlGenerator = $url_generator;
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
    $user_data = $this->userData->get('overlay', $user->id(), 'enabled');
    $use_overlay = !isset($user_data) || $user_data;
    if (empty($mode) && user_access('access overlay') && $use_overlay) {
      $current_path = $request->attributes->get('_system_path');
      // After overlay is enabled on the modules page, redirect to
      // <front>#overlay=admin/modules to actually enable the overlay.
      if (isset($_SESSION['overlay_enable_redirect']) && $_SESSION['overlay_enable_redirect']) {
        unset($_SESSION['overlay_enable_redirect']);
        $url = $this->urlGenerator
          ->generateFromPath('<front>', array(
            'fragment' => 'overlay=' . $current_path,
            'absolute' => TRUE,
          ));
        $response = new RedirectResponse($url);
        $event->setResponse($response);
      }

      if ($request->query->get('render') == 'overlay') {
        // If a previous page requested that we close the overlay, close it and
        // redirect to the final destination.
        if (isset($_SESSION['overlay_close_dialog'])) {
          $response = call_user_func_array('overlay_close_dialog', $_SESSION['overlay_close_dialog']);
          unset($_SESSION['overlay_close_dialog']);
          $event->setResponse($response);
        }
        // If this page shouldn't be rendered inside the overlay, redirect to
        // the parent.
        elseif (!path_is_admin($current_path)) {
          $response = overlay_close_dialog($current_path, array('query' => drupal_get_query_parameters(NULL, array('render'))));
          $event->setResponse($response);
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
      $response = $event->getResponse();
      if ($response instanceOf RedirectResponse) {
        $path = $response->getTargetUrl();
        // The authorize.php script bootstraps Drupal to a very low level, where
        // the PHP code that is necessary to close the overlay properly will not
        // be loaded. Therefore, if we are redirecting to authorize.php inside
        // the overlay, instead redirect back to the current page with
        // instructions to close the overlay there before redirecting to the
        // final destination.
        $options = array('absolute' => TRUE);
        if ($path == system_authorized_get_url($options) || $path == system_authorized_batch_processing_url($options)) {
          $_SESSION['overlay_close_dialog'] = array($path, $options);
          $path = current_path();
          $options = drupal_get_query_parameters();
        }

        // If the current page request is inside the overlay, add ?render=overlay
        // to the new path, so that it appears correctly inside the overlay.
        if (isset($options['query'])) {
          $options['query'] += array('render' => 'overlay');
        }
        else {
          $options['query'] = array('render' => 'overlay');
        }
        $response->setTargetUrl($this->urlGenerator->generateFromPath($path, $options));
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
