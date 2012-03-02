<?php

namespace Drupal\Core;

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher as SymfonyUrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * UrlMatcher matches URL based on a set of routes.
 */
class UrlMatcher extends SymfonyUrlMatcher {

  protected $context;

  /**
   * Constructor.
   *
   * @param RequestContext  $context
   *   The request context object.
   */
  public function __construct(RequestContext $context) {
    $this->context = $context;
  }

  /**
   * {@inheritDoc}
   *
   * @api
   */
  public function match($pathinfo) {

    $this->allow = array();

    // Symfony uses a prefixing / but we don't yet.
    $dpathinfo = ltrim($pathinfo, '/');

    // Do our fancy frontpage logic.
    if (empty($dpathinfo)) {
      $dpathinfo = variable_get('site_frontpage', 'user');
      $pathinfo = '/' . $dpathinfo;
    }

    if ($router_item = $this->matchDrupalItem($dpathinfo)) {

      $routes = new RouteCollection();
      $routes->add(hash('sha256', $router_item['path']), $this->convertDrupalItem($router_item));

      if ($ret = $this->matchCollection($pathinfo, $routes)) {
        //drupal_set_message('<pre>' . var_export('test', TRUE) . '</pre>');
        // Stash the router item in the attributes while we're transitioning.
        $ret['drupal_menu_item'] = $router_item;

        // Most legacy controllers (aka page callbacks) are in a separate file,
        // so we have to include that.
        if ($router_item['include_file']) {
          require_once DRUPAL_ROOT . '/' . $router_item['include_file'];
        }

        return $ret;
      }
    }

    throw 0 < count($this->allow)
      ? new MethodNotAllowedException(array_unique(array_map('strtoupper', $this->allow)))
      : new ResourceNotFoundException();
  }

  /**
   * Get a drupal menu item.
   *
   * @todo Make this return multiple possible candidates for the resolver to
   * consider.
   *
   * @param string $path
   *   The path being looked up by
   */
  protected function matchDrupalItem($path) {
    // For now we can just proxy our procedural method. At some point this will
    // become more complicated because we'll need to get back candidates for a
    // path and them resolve them based on things like method and scheme which
    // we currently can't do.
    $item = menu_get_item($path);
    $path_elements = explode('/', $item['path']);
    $i = 0;
    foreach ($path_elements as $key => $element) {
      if ($element == '%') {
        $path_elements[$key] = "{arg$i}";
        $i++;
      }
    }
    $item['path'] = implode('/', $path_elements);
    return $item;
  }

  protected function convertDrupalItem($router_item) {
    $route = array(
      '_controller' => $router_item['page_callback']
    );
    // Place argument defaults on the route.
    foreach ($router_item['page_arguments'] as $k => $v) {
      $route[$k] = $v;
    }
    return new Route($router_item['path'], $route);
  }
}
