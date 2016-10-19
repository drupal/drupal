<?php

namespace Drupal\Core\Routing;

use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides helpers for redirect destinations.
 */
class RedirectDestination implements RedirectDestinationInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The destination used by the current request.
   *
   * @var string
   */
  protected $destination;

  /**
   * Constructs a new RedirectDestination instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(RequestStack $request_stack, UrlGeneratorInterface $url_generator) {
    $this->requestStack = $request_stack;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function getAsArray() {
    return ['destination' => $this->get()];
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    if (!isset($this->destination)) {
      $query = $this->requestStack->getCurrentRequest()->query;
      if (UrlHelper::isExternal($query->get('destination'))) {
        $this->destination = '/';
      }
      elseif ($query->has('destination')) {
        $this->destination = $query->get('destination');
      }
      else {
        $this->destination = $this->urlGenerator->generateFromRoute('<current>', [], ['query' => UrlHelper::filterQueryParameters($query->all())]);
      }
    }

    return $this->destination;
  }

  /**
   * {@inheritdoc}
   */
  public function set($new_destination) {
    $this->destination = $new_destination;
    return $this;
  }

}
