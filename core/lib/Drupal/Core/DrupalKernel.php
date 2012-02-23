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

use Exception;

/**
 * @file
 *
 * Definition of Drupal\Core\DrupalKernel.
 */

/**
 * The DrupalKernel is the main routing and dispatching routine in Drupal.
 */
class DrupalKernel implements HttpKernelInterface {

  function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true) {
    try {

      $dispatcher = new EventDispatcher();

      //$dispatcher->addSubscriber(new \Symfony\Component\HttpKernel\EventListener\ExceptionListener());


      // Quick and dirty attempt at wrapping our rendering logic as is.
      $dispatcher->addListener(KernelEvents::VIEW, function(Event $event) {
        $page_callback_result = $event->getControllerResult();
        $event->setResponse(new Response(drupal_render_page($page_callback_result)));
      });
      $dispatcher->addListener(KernelEvents::EXCEPTION, function(Event $event) use ($request) {
        debug($request->getAcceptableContentTypes());

        if (in_array('text/html', $request->getAcceptableContentTypes())) {
          if ($event->getException() instanceof ResourceNotFoundException) {
            $event->setResponse(new Response('Not Found', 404));
          }
        }



      });


      // Resolve a routing context(path, etc) using the routes object to a
      // Set a routing context to translate.
      $context = new RequestContext();
      $context->fromRequest($request);
      $matcher = new UrlMatcher($context);
      // Push path paramaters into attributes.
      $request->attributes->add($matcher->match($request->getPathInfo()));

      // Get the controller(page callback) from the resolver.
      $resolver = new ControllerResolver();
      $controller = $resolver->getController($request);
      $arguments = $resolver->getArguments($request, $controller);

      $kernel = new HttpKernel($dispatcher, $resolver);
      $response = $kernel->handle($request);
    }
    catch (Exception $e) {
      $error_event = new GetResponseForExceptionEvent($this, $request, $this->type, $e);
      $dispatcher->dispatch(KernelEvents::EXCEPTION, $error_event);
      if ($error_event->hasResponse()) {
        $response = $error_event->getResponse();
      }
      else {
        $response = new Response('An error occurred', 500);
      }


      //$response = new Response('Not Found', 404);
    }
    //catch (Exception $e) {
    //  $response = new Response('An error occurred', 500);
    //}

    return $response;
  }
}
