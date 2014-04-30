<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\ViewSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Ajax\AjaxResponseRenderer;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Page\HtmlPage;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Drupal\Core\ContentNegotiation;

/**
 * Main subscriber for VIEW HTTP responses.
 *
 * @todo This needs to get refactored to be extensible so that we can handle
 *   more than just Html and Drupal-specific JSON requests. See
 *   http://drupal.org/node/1594870
 */
class ViewSubscriber implements EventSubscriberInterface {

  /**
   * The content negotiation.
   *
   * @var \Drupal\Core\ContentNegotiation
   */
  protected $negotiation;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The Ajax response renderer.
   *
   * @var \Drupal\Core\Ajax\AjaxResponseRenderer
   */
  protected $ajaxRenderer;

  /**
   * Constructs a new ViewSubscriber.
   *
   * @param \Drupal\Core\ContentNegotiation $negotiation
   *   The content negotiation.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Drupal\Core\Ajax\AjaxResponseRenderer $ajax_renderer
   *   The ajax response renderer.
   */
  public function __construct(ContentNegotiation $negotiation, TitleResolverInterface $title_resolver, AjaxResponseRenderer $ajax_renderer) {
    $this->negotiation = $negotiation;
    $this->titleResolver = $title_resolver;
    $this->ajaxRenderer = $ajax_renderer;
  }

  /**
   * Processes a successful controller into an HTTP 200 response.
   *
   * Some controllers may not return a response object but simply the body of
   * one.  The VIEW event is called in that case, to allow us to mutate that
   * body into a Response object.  In particular we assume that the return
   * from an JSON-type response is a JSON string, so just wrap it into a
   * Response object.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent $event
   *   The Event to process.
   */
  public function onView(GetResponseForControllerResultEvent $event) {

    $request = $event->getRequest();

    // For a master request, we process the result and wrap it as needed.
    // For a subrequest, all we want is the string value.  We assume that
    // is just an HTML string from a controller, so wrap that into a response
    // object.  The subrequest's response will get dissected and placed into
    // the larger page as needed.
    if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
      $method = 'on' . $this->negotiation->getContentType($request);

      if (method_exists($this, $method)) {
        $event->setResponse($this->$method($event));
      }
      else {
        $event->setResponse(new Response('Not Acceptable', 406));
      }
    }
    else {
      // This is a new-style Symfony-esque subrequest, which means we assume
      // the body is not supposed to be a complete page but just a page
      // fragment.
      $page_result = $event->getControllerResult();
      if ($page_result instanceof HtmlPage || $page_result instanceof Response) {
        return $page_result;
      }
      if (!is_array($page_result)) {
        $page_result = array(
          '#markup' => $page_result,
        );
      }

      // If no title was returned fall back to one defined in the route.
      if (!isset($page_result['#title'])) {
        $page_result['#title'] = $this->titleResolver->getTitle($request, $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT));
      }

      $event->setResponse(new Response(drupal_render($page_result)));
    }
  }

  public function onJson(GetResponseForControllerResultEvent $event) {
    $page_callback_result = $event->getControllerResult();

    $response = new JsonResponse();
    $response->setData($page_callback_result);

    return $response;
  }

  public function onIframeUpload(GetResponseForControllerResultEvent $event) {
    $response = $event->getResponse();

    // Browser IFRAMEs expect HTML. Browser extensions, such as Linkification
    // and Skype's Browser Highlighter, convert URLs, phone numbers, etc. into
    // links. This corrupts the JSON response. Protect the integrity of the
    // JSON data by making it the value of a textarea.
    // @see http://malsup.com/jquery/form/#file-upload
    // @see http://drupal.org/node/1009382
    $html = '<textarea>' . $response->getContent() . '</textarea>';

    return new Response($html);
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = array('onView');

    return $events;
  }
}
