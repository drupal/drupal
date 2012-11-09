<?php

/**
 * @file
 * Definition of Drupal\rest\RequestHandler.
 */

namespace Drupal\rest;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Acts as intermediate request forwarder for resource plugins.
 */
class RequestHandler extends ContainerAware {

  /**
   * Handles a web API request.
   *
   * @param string $plugin
   *   The resource type plugin.
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   * @param mixed $id
   *   The resource ID.
   *
   * @todo Remove $plugin as argument. After http://drupal.org/node/1793520 is
   *   committed we would be able to access route object as
   *   $request->attributes->get('_route'). Then we will get plugin as
   *   '_plugin' property of route object.
   */
  public function handle($plugin, Request $request, $id = NULL) {
    $method = strtolower($request->getMethod());
    if (user_access("restful $method $plugin")) {
      $resource = $this->container
        ->get('plugin.manager.rest')
        ->getInstance(array('id' => $plugin));
      try {
        return $resource->{$method}($id);
      }
      catch (HttpException $e) {
        return new Response($e->getMessage(), $e->getStatusCode(), $e->getHeaders());
      }
    }
    return new Response('Access Denied', 403);
  }
}
