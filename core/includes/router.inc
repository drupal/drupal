<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\UrlMatcher;

/**
 * Execute the page callback associated with given request.
 *
 * @param Request $request
 *   The Symfony Request object used to determine what will be executed.
 * @return Response
 *   A Symfony Response object describing the response to deliver to the client.
 */
function router_execute_request(Request $request) {

  try {
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

    $dispatcher = new EventDispatcher();
    // Quick and dirty attempt at wrapping our rendering logic as is.
    $dispatcher->addListener(KernelEvents::VIEW, function(Event $event) {
      $page_callback_result = $event->getControllerResult();
      $event->setResponse(new Response(drupal_render_page($page_callback_result)));
    });

    $kernel = new HttpKernel($dispatcher, $resolver);
    return $kernel->handle($request);
  }
  catch (ResourceNotFoundException $e) {
    $response = new Response('Not Found', 404);
  }
  //catch (Exception $e) {
  //  $response = new Response('An error occurred', 500);
  //}

  return $response;
}
