<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Drupal\Core\DrupalKernel;

/**
 * @file
 *
 * Definition of Drupal\Core\EventSubscriber\HtmlSubscriber;
 */

/**
 * Main subscriber for HTML-type HTTP responses.
 */
class HtmlSubscriber implements EventSubscriberInterface {

  /**
   * Determines if we are dealing with an HTML-style response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   * @return boolean
   *   True if it is an event we should process as HTML, False otherwise.
   */
  protected function isHtmlRequestEvent(GetResponseEvent $event) {
    return (boolean)array_intersect(array('text/html', '*/*'), $event->getRequest()->getAcceptableContentTypes());
  }

  /**
   * Processes an AccessDenied exception into an HTTP 403 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onAccessDeniedException(GetResponseEvent $event) {
    if ($this->isHtmlRequestEvent($event) && $event->getException() instanceof AccessDeniedHttpException) {
      $event->setResponse(new Response('Access Denied', 403));
    }
  }

  /**
   * Processes a NotFound exception into an HTTP 404 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onNotFoundHttpException(GetResponseEvent $event) {
    if ($this->isHtmlRequestEvent($event) && $event->getException() instanceof NotFoundHttpException) {

      watchdog('page not found', check_plain($_GET['q']), NULL, WATCHDOG_WARNING);

      // Check for and return a fast 404 page if configured.
      // @todo Inline this rather than using a function.
      drupal_fast_404();

      $system_path = $event->getRequest()->attributes->get('system_path');

      // Keep old path for reference, and to allow forms to redirect to it.
      if (!isset($_GET['destination'])) {
        $_GET['destination'] = $system_path;
      }

      $path = drupal_get_normal_path(variable_get('site_404', ''));
      if ($path && $path != $system_path) {
        // @TODO: Um, how do I specify an override URL again? Totally not clear.
        // Do that and sub-call the kernel rather than using meah().
        $request = Request::create($path);

        $kernel = new DrupalKernel();
        $response = $kernel->handle($request, DrupalKernel::SUB_REQUEST);
        $response->setStatusCode(404, 'Not Found');
      }
      else {
        $response = new Response('Not Found', 404);

        // @todo Replace this block with something cleaner.
        $return = t('The requested page "@path" could not be found.', array('@path' => $event->getRequest()->getPathInfo()));
        drupal_set_title(t('Page not found'));
        drupal_set_page_content($return);
        $page = element_info('page');
        $content = drupal_render_page($page);

        $response->setContent($content);
      }

      $event->setResponse($response);
    }
  }

  /**
   * Processes a MethodNotAllowed exception into an HTTP 405 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onMethodAllowedException(GetResponseEvent $event) {
    if ($this->isHtmlRequestEvent($event) && $event->getException() instanceof MethodNotAllowedException) {
      $event->setResponse(new Response('Method Not Allowed', 405));
    }
  }

  /**
   * Processes a successful controller into an HTTP 200 response.
   *
   * Some controllers may not return a response object but simply the body of
   * one.  The VIEW event is called in that case, to allow us to mutate that
   * body into a Response object.  In particular we assume that the return
   * from an HTML-type response is a render array from a legacy page callback
   * and render it.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onView(GetResponseEvent $event) {
    if ($this->isHtmlRequestEvent($event)) {
      $page_callback_result = $event->getControllerResult();
      $event->setResponse(new Response(drupal_render_page($page_callback_result)));
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    // Since we want HTML to be our default, catch-all response type, give its
    // listeners a very low priority so that they always check last.
    $events[KernelEvents::EXCEPTION][] = array('onNotFoundHttpException', -5);
    $events[KernelEvents::EXCEPTION][] = array('onAccessDeniedException', -5);
    $events[KernelEvents::EXCEPTION][] = array('onMethodAllowedException', -5);

    $events[KernelEvents::VIEW][] = array('onView', -5);

    return $events;
  }
}
