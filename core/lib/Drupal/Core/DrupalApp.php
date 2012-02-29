<?php

namespace Drupal\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;

use Drupal\Core\EventSubscriber\HtmlSubscriber;
use Drupal\Core\EventSubscriber\AccessSubscriber;
use Drupal\Core\EventSubscriber\PathSubscriber;

use Exception;

/**
 * @file
 *
 * Definition of Drupal\Core\DrupalApp.
 */

/**
 * The DrupalApp class is the core of Drupal itself.
 */
class DrupalApp implements HttpKernelInterface {

  /**
   *
   * @param Request $request
   *   The request to process.
   * @return Response
   *   The response object to return to the requesting user agent.
   */
  function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true) {
    try {

      $dispatcher = $this->getDispatcher();

      $matcher = $this->getMatcher($request);
      $dispatcher->addSubscriber(new RouterListener($matcher));
      $dispatcher->addSubscriber(new AccessSubscriber());
      $dispatcher->addSubscriber(new PathSubscriber());

      $resolver = new ControllerResolver();

      $kernel = new HttpKernel($dispatcher, $resolver);
      $response = $kernel->handle($request);
    }
    catch (Exception $e) {
      // Some other form of error occured that wasn't handled by another kernel
      // listener.  That could mean that it's a method/mime-type/error
      // combination that is not accounted for, or some other type of error.
      // Either way, treat it as a server-level error and return an HTTP 500.
      // By default, this will be an HTML-type response because that's a decent
      // best guess if we don't know otherwise.
      $response = new Response('A fatal error occurred: ' . $e->getMessage(), 500);
    }

    return $response;
  }

  /**
   * Returns an EventDispatcher for the Kernel to use.
   *
   * The EventDispatcher is pre-wired with some event listeners/subscribers.
   *
   * @todo Make the listeners that get attached extensible, but without using
   * hooks.
   *
   * @return EventDispatcher
   */
  protected function getDispatcher() {
    $dispatcher = new EventDispatcher();

    // @todo Make this extensible rather than just hard coding some.
    // @todo Add a subscriber to handle other things, too, like our Ajax
    // replacement system.
    $dispatcher->addSubscriber(new HtmlSubscriber());

    return $dispatcher;
  }

  /**
   * Returns a UrlMatcher object for the specified request.
   *
   * @param Request $request
   *   The request object for this matcher to use.
   * @return UrlMatcher
   */
  protected function getMatcher(Request $request) {
    // Resolve a routing context(path, etc) using the routes object to a
    // Set a routing context to translate.
    $context = new RequestContext();
    $context->fromRequest($request);
    $matcher = new UrlMatcher($context);

    return $matcher;
  }
}
