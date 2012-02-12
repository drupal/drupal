<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing;
use Symfony\Component\HttpKernel;

function router_execute_active_handler($request) {
  // Do some hand waving to setup the routing.
  $routes = router_get_routes($request);

  try {
    // Resolve a routing context(path, etc) using the routes object to a
    // Set a /routing/ context to translate
    $context = new Routing\RequestContext();
    $context->fromRequest($request);
    $matcher = new Routing\Matcher\UrlMatcher($routes, $context);
    $request->attributes->add($matcher->match($request->getPathInfo()));

    // Get the controller(page callback) from the resolver.
    $resolver = new HttpKernel\Controller\ControllerResolver();
    $controller = $resolver->getController($request);
    $arguments = $resolver->getArguments($request, $controller);

    $response = call_user_func_array($controller, $arguments);
  }
  catch (Routing\Exception\ResourceNotFoundException $e) {
    $response = new Response('Not Found', 404);
  }
  //catch (Exception $e) {
  //  $response = new Response('An error occurred', 500);
  //}

  return $response;
}

/**
 * Get a RouteCollection for resolving a request.
 *
 * Ok, so... we need a routing collection that's not this "dumb". Symfony's just
 * is just a trivial implementation. It probably means we need our own
 * DrupalRouteCollection which would actually wrap this logic, our menu router
 * table, translating between it, and caching.
 */
function router_get_routes($request) {
  $routes = new Routing\RouteCollection();
  foreach (module_list() as $module) {
    $func = $module . '_menu';
    $items = $func();

    foreach ($items as $path => $item) {
      // Drupal doesn't prefix but if someone did we wouldn't want to double up.
      if (0 !== strpos($path, '/')) {
        $path = '/' . $path;
      }

      // Set base route array.
      $route = array(
        // A page callback could be a router. I'm not sure if the controller
        // should actually be a thin layer on top or work like this yet.
        '_controller' => $item['page callback'],
      );

      // Place argument defaults on the route.
      foreach ($item['page arguments'] as $k => $v) {
        $route[$k] = $v;
      }
      // @todo put other "menu" information somewhere.

      $routes->add(hash('sha256', $path), new Routing\Route($path, $route));
    }
  }

  return $routes;
}
