<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing;
use Symfony\Component\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Drupal\Core\UrlMatcher;


/**
 * Execute the page callback associated with the current path.
 *
 * @param $path
 *   The drupal path whose handler is to be be executed. If set to NULL, then
 *   the current path is used.
 * @param $deliver
 *   (optional) A boolean to indicate whether the content should be sent to the
 *   browser using the appropriate delivery callback (TRUE) or whether to return
 *   the result to the caller (FALSE).
 */
function router_execute_active_handler($request) {

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
