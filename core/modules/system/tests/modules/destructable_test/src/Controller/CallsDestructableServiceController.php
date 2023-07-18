<?php

namespace Drupal\destructable_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\destructable_test\Destructable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to instantiate the destructable service.
 */
final class CallsDestructableServiceController extends ControllerBase {

  /**
   * Destructable service.
   *
   * @var \Drupal\destructable_test\Destructable
   */
  protected $destructable;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get(Destructable::class));
  }

  public function __construct(Destructable $destructable) {
    $this->destructable = $destructable;
  }

  /**
   * Render callback.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response.
   */
  public function render(Request $request): Response {
    $this->destructable->setSemaphore($request->query->get('semaphore'));
    return new Response('This is a longer-ish string of content to send to the client, to invoke any trivial transfer buffers both on the server and client side.');
  }

}
