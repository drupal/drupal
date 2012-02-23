<?php

namespace Drupal\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

use Drupal\Core\EventSubscriber\HtmlSubscriber;

use Exception;

/**
 * @file
 *
 * Definition of Drupal\Core\DrupalApp.
 */

/**
 * The DrupalApp class is the core of Drupal itself.
 */
class DrupalApp {

  function execute(Request $request) {
    try {

      $dispatcher = $this->getDispatcher();

      $matcher = $this->getMatcher($request);

      // Push path paramaters into attributes.
      $request->attributes->add($matcher->match($request->getPathInfo()));

      $resolver = $this->getControllerResolver($request);

      $kernel = new HttpKernel($dispatcher, $resolver);
      $response = $kernel->handle($request);
    }
    catch (Exception $e) {
      $error_event = new GetResponseForExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $e);
      $dispatcher->dispatch(KernelEvents::EXCEPTION, $error_event);
      if ($error_event->hasResponse()) {
        $response = $error_event->getResponse();
      }
      else {
        $response = new Response('An error occurred', 500);
      }
    }

    return $response;
  }

  protected function getDispatcher() {
    $dispatcher = new EventDispatcher();

    // @todo Make this extensible rather than just hard coding some.
    // @todo Add a subscriber to handle other things, too, like our Ajax
    // replacement system.
    $dispatcher->addSubscriber(new HtmlSubscriber());

    return $dispatcher;
  }

  protected function getMatcher(Request $request) {
    // Resolve a routing context(path, etc) using the routes object to a
    // Set a routing context to translate.
    $context = new RequestContext();
    $context->fromRequest($request);
    $matcher = new UrlMatcher($context);

    return $matcher;
  }

  protected function getControllerResolver($request) {
    // Get the controller(page callback) from the resolver.
    $resolver = new ControllerResolver();
    $controller = $resolver->getController($request);
    $arguments = $resolver->getArguments($request, $controller);

    return $resolver;
  }

}
