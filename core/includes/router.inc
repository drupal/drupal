<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing;
use Symfony\Component\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Matcher\UrlMatcher as SymfonyUrlMatcher;


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
  // Do some hand waving to setup the routing.
  $routes = router_get_routes($request);

  try {
    // Resolve a routing context(path, etc) using the routes object to a
    // Set a /routing/ context to translate
    // @todo probably write our own route object and UrlMatcher to handle
    // converting Drupal's db driven backend.
    $context = new Routing\RequestContext();
    $context->fromRequest($request);
    $matcher = new SymfonyUrlMatcher($routes, $context);
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

/**
 * Get a RouteCollection for resolving a request.
 *
 * Ok, so... we need a routing collection that's not this "dumb". Symfony's just
 * is just a trivial implementation. It probably means we need our own
 * DrupalRouteCollection which would actually wrap this logic, our menu router
 * table, translating between it, and caching.
 */
function router_get_routes($request) {

  // Rebuild if we know it's needed, or if the menu masks are missing which
  // occurs rarely, likely due to a race condition of multiple rebuilds.
  if (variable_get('menu_rebuild_needed', FALSE) || !variable_get('menu_masks', array())) {
    menu_rebuild();
  }


  $routes = new Routing\RouteCollection();
  $foo = db_query('SELECT path, page_callback, page_arguments FROM {menu_router}');
  while ($router_item = $foo->fetchAssoc()) {
    $route = array(
      'path' => $router_item['path'],
      '_controller' => $router_item['page_callback'],
    );
    if (0 !== strpos($route['path'], '/')) {
      $route['path'] = '/' . $route['path'];
    }

    // Place argument defaults on the route.
    foreach (unserialize($router_item['page_arguments']) as $k => $v) {
      $route[$k] = $v;
    }

    $routes->add(hash('sha256', $route['path']), new Routing\Route($route['path'], $route));
  }

  return $routes;

  // Since there is no limit to the length of $path, use a hash to keep it
  // short yet unique.
  $cid = 'menu_item:' . hash('sha256', $path);
  if ($cached = cache('menu')->get($cid)) {
    $router_item = $cached->data;
  }
  else {
    $parts = array_slice($original_map, 0, MENU_MAX_PARTS);
    $ancestors = menu_get_ancestors($parts);
    $router_item = db_query_range('SELECT * FROM {menu_router} WHERE path IN (:ancestors) ORDER BY fit DESC', 0, 1, array(':ancestors' => $ancestors))->fetchAssoc();
    cache('menu')->set($cid, $router_item);
  }

  $routes = new Routing\RouteCollection();
  foreach (module_list() as $module) {
    $items = module_invoke($module, 'menu');

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
