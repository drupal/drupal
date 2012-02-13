<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing;
use Symfony\Component\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
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
    $context = new Routing\RequestContext();
    $context->fromRequest($request);
    $matcher = new UrlMatcher($context);
    // Push path paramaters into attributes.
    $request->attributes->add($matcher->match($request->getPathInfo()));

    // Get the controller(page callback) from the resolver.
    $resolver = new HttpKernel\Controller\ControllerResolver();
    $controller = $resolver->getController($request);
    $arguments = $resolver->getArguments($request, $controller);

    $dispatcher = new EventDispatcher();

    $kernel = new HttpKernel\HttpKernel($dispatcher, $resolver);
    return $kernel->handle($request);
  }
  catch (Routing\Exception\ResourceNotFoundException $e) {
    $response = new Response('Not Found', 404);
  }
  //catch (Exception $e) {
  //  $response = new Response('An error occurred', 500);
  //}

  return $response;
}
