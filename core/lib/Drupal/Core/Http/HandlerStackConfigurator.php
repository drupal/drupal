<?php

/**
 * @file
 * Contains \Drupal\Core\Http\HandlerStackConfigurator.
 */

namespace Drupal\Core\Http;

use GuzzleHttp\HandlerStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for configuring middlewares on the http handler stack.
 *
 * The http_client service requires a handler stack to perform http requests.
 * This is provided by the http_handler_stack service. Modules wishing to add
 * additional middlewares to the handler stack can create services and tag them
 * as http_client_middleware. Each service must contain an __invoke method that
 * returns a closure which will serve as the middleware.
 *
 * @see https://guzzle.readthedocs.org/en/latest/handlers-and-middleware.html
 *
 * @see \Drupal\Core\Http\Client
 * @see \Drupal\Core\Test\HttpClientMiddleware\TestHttpClientMiddleware
 */
class HandlerStackConfigurator {

  /**
   * Array of middlewares to add to the handler stack.
   *
   * @var callable[]
   */
  protected $middlewares = NULL;

  /**
   * A list of used middleware service IDs.
   *
   * @var string[]
   */
  protected $middlewareIds = [];

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Contructs a new HandlerStackConfigurator object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param string[] $middleware_ids
   *   The middleware IDs.
   */
  public function __construct(ContainerInterface $container, array $middleware_ids) {
    $this->middlewareIds = $middleware_ids;
    $this->container = $container;
  }

  /**
   * Ensures that the middlewares are initialized.
   */
  protected function initializeMiddlewares() {
    if (!isset($this->middlewares)) {
      $this->middlewares = [];
      foreach ($this->middlewareIds as $middleware_id) {
        $middleware = $this->container->get($middleware_id);
        if (is_callable($middleware)) {
          $this->middlewares[$middleware_id] = $middleware();
        }
        else {
          throw new \InvalidArgumentException('Middlewares need to implement __invoke, see https://guzzle.readthedocs.org/en/latest/handlers-and-middleware.html for more information about middlewares.');
        }
      }
    }
  }

  /**
   * Configures the stack using services tagged as http_client_middleware.
   *
   * @param \GuzzleHttp\HandlerStack $handler_stack
   *   The handler stack
   */
  public function configure(HandlerStack $handler_stack) {
    $this->initializeMiddlewares();
    foreach ($this->middlewares as $middleware_id => $middleware) {
      $handler_stack->push($middleware, $middleware_id);
    }
  }

}
