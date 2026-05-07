<?php

namespace Drupal\Core\RouteProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Routing\Route;

/**
 * Route processor manager.
 *
 * Holds an array of route processor objects and uses them to sequentially
 * process an outbound route, in order of processor priority.
 */
class RouteProcessorManager implements OutboundRouteProcessorInterface {

  public function __construct(
    #[AutowireIterator(tag: 'route_processor_outbound')]
    protected readonly iterable $outboundProcessors = [],
  ) {}

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, ?BubbleableMetadata $bubbleable_metadata = NULL) {
    foreach ($this->outboundProcessors as $processor) {
      $processor->processOutbound($route_name, $route, $parameters, $bubbleable_metadata);
    }
  }

}
