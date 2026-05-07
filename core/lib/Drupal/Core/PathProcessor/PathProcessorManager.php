<?php

namespace Drupal\Core\PathProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor manager.
 *
 * Holds an array of path processor objects and uses them to sequentially
 * process a path, in order of processor priority.
 */
class PathProcessorManager implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  public function __construct(
    #[AutowireIterator(tag: 'path_processor_inbound')]
    protected readonly iterable $inboundProcessors = [],
    #[AutowireIterator(tag: 'path_processor_outbound')]
    protected readonly iterable $outboundProcessors = [],
  ) {}

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    foreach ($this->inboundProcessors as $processor) {
      $path = $processor->processInbound($path, $request);
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], ?Request $request = NULL, ?BubbleableMetadata $bubbleable_metadata = NULL) {
    foreach ($this->outboundProcessors as $processor) {
      $path = $processor->processOutbound($path, $options, $request, $bubbleable_metadata);
    }
    return $path;
  }

}
