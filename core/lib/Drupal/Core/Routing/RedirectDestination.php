<?php

namespace Drupal\Core\Routing;

use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides helpers for redirect destinations.
 */
class RedirectDestination implements RedirectDestinationInterface {

  /**
   * The destination used by the current request.
   *
   * @var string
   */
  protected $destination;

  /**
   * Constructs a new RedirectDestination instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   The URL generator.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected UrlGeneratorInterface $urlGenerator,
  ) {
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
      if ($query->has('destination')) {
        $this->destination = $query->get('destination');
        if (UrlHelper::isExternal($this->destination)) {
          // See https://www.drupal.org/node/2454955 for external redirects.
          $this->destination = '/';
        }
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
