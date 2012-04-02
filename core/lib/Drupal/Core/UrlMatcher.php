<?php

/**
 * @file
 *
 * Definition of Drupal\Core\UrlMatcher.
 */

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

  /**
   * The request context for this matcher.
   *
   * @var RequestContext
   */
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
      $ret = $this->convertDrupalItem($router_item);
      // Stash the router item in the attributes while we're transitioning.
      $ret['drupal_menu_item'] = $router_item;

      // Most legacy controllers (aka page callbacks) are in a separate file,
      // so we have to include that.
      if ($router_item['include_file']) {
        require_once DRUPAL_ROOT . '/' . $router_item['include_file'];
      }

      return $ret;
    }

    // This matcher doesn't differentiate by method, so don't bother with those
    // exceptions.
    throw new ResourceNotFoundException();
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
    return menu_get_item($path);
  }

  protected function convertDrupalItem($router_item) {
    $route = array(
      '_controller' => $router_item['page_callback']
    );

    // Place argument defaults on the route.
    // @TODO: For some reason drush test runs have a serialized page_arguments
    // but HTTP requests are unserialized. Hack to get around this for now.
    // This might be because page arguments aren't unserialized in
    // menu_get_item() when the access is denied.
    !is_array($router_item['page_arguments']) ? $page_arguments = unserialize($router_item['page_arguments']) : $page_arguments = $router_item['page_arguments'];
    foreach ($page_arguments as $k => $v) {
      $route[$k] = $v;
    }
    return $route;
    return new Route($router_item['href'], $route);
  }
}
