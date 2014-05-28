<?php

/**
 * @file
 * Definition of Drupal\rest\RequestHandler.
 */

namespace Drupal\rest;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Acts as intermediate request forwarder for resource plugins.
 */
class RequestHandler implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Handles a web API request.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function handle(Request $request) {

    $plugin = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)->getDefault('_plugin');
    $method = strtolower($request->getMethod());

    $resource = $this->container
      ->get('plugin.manager.rest')
      ->getInstance(array('id' => $plugin));

    // Deserialze incoming data if available.
    $serializer = $this->container->get('serializer');
    $received = $request->getContent();
    $unserialized = NULL;
    if (!empty($received)) {
      $format = $request->getContentType();

      // Only allow serialization formats that are explicitly configured. If no
      // formats are configured allow all and hope that the serializer knows the
      // format. If the serializer cannot handle it an exception will be thrown
      // that bubbles up to the client.
      $config = $this->container->get('config.factory')->get('rest.settings')->get('resources');
      $method_settings = $config[$plugin][$request->getMethod()];
      if (empty($method_settings['supported_formats']) || in_array($format, $method_settings['supported_formats'])) {
        $definition = $resource->getPluginDefinition();
        $class = $definition['serialization_class'];
        try {
          $unserialized = $serializer->deserialize($received, $class, $format, array('request_method' => $method));
        }
        catch (UnexpectedValueException $e) {
          $error['error'] = $e->getMessage();
          $content = $serializer->serialize($error, $format);
          return new Response($content, 400, array('Content-Type' => $request->getMimeType($format)));
        }
      }
      else {
        throw new UnsupportedMediaTypeHttpException();
      }
    }

    // Determine the request parameters that should be passed to the resource
    // plugin.
    $route_parameters = $request->attributes->get('_route_params');
    $parameters = array();
    // Filter out all internal parameters starting with "_".
    foreach ($route_parameters as $key => $parameter) {
      if ($key{0} !== '_') {
        $parameters[] = $parameter;
      }
    }

    // Invoke the operation on the resource plugin.
    // All REST routes are restricted to exactly one format, so instead of
    // parsing it out of the Accept headers again, we can simply retrieve the
    // format requirement. If there is no format associated, just pick JSON.
    $format = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)->getRequirement('_format') ?: 'json';
    try {
      $response = call_user_func_array(array($resource, $method), array_merge($parameters, array($unserialized, $request)));
    }
    catch (HttpException $e) {
      $error['error'] = $e->getMessage();
      $content = $serializer->serialize($error, $format);
      // Add the default content type, but only if the headers from the
      // exception have not specified it already.
      $headers = $e->getHeaders() + array('Content-Type' => $request->getMimeType($format));
      return new Response($content, $e->getStatusCode(), $headers);
    }

    // Serialize the outgoing data for the response, if available.
    $data = $response->getResponseData();
    if ($data != NULL) {
      $output = $serializer->serialize($data, $format);
      $response->setContent($output);
      $response->headers->set('Content-Type', $request->getMimeType($format));
    }
    return $response;
  }

  /**
   * Generates a CSRF protecting session token.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function csrfToken() {
    return new Response(drupal_get_token('rest'), 200, array('Content-Type' => 'text/plain'));
  }
}
