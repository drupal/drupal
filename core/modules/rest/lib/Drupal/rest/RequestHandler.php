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
    $resource = $this->container
      ->get('plugin.manager.rest')
      ->getInstance(array('id' => $plugin));
    $received = $request->getContent();
    // @todo De-serialization should happen here if the request is supposed
    // to carry incoming data.
    try {
      $response = $resource->{$method}($id, $received);
    }
    catch (HttpException $e) {
      return new Response($e->getMessage(), $e->getStatusCode(), $e->getHeaders());
    }
    $data = $response->getResponseData();
    if ($data != NULL) {
      // Serialize the response data.
      $serializer = $this->container->get('serializer');
      // @todo Replace the format here with something we get from the HTTP
      //   Accept headers. See http://drupal.org/node/1833440
      $output = $serializer->serialize($data, 'drupal_jsonld');
      $response->setContent($output);
      $response->headers->set('Content-Type', 'application/vnd.drupal.ld+json');
    }
    return $response;
  }
}
