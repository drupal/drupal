<?php

namespace Drupal\Core\PathProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor manager.
 *
 * Holds an array of path processor objects and uses them to sequentially process
 * a path, in order of processor priority.
 */
class PathProcessorManager implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * Holds the array of inbound processors to cycle through.
   *
   * @var array
   *   An array whose keys are priorities and whose values are arrays of path
   *   processor objects.
   */
  protected $inboundProcessors = [];

  /**
   * Holds the array of inbound processors, sorted by priority.
   *
   * @var array
   *   An array of path processor objects.
   */
  protected $sortedInbound = [];


  /**
   * Holds the array of outbound processors to cycle through.
   *
   * @var array
   *   An array whose keys are priorities and whose values are arrays of path
   *   processor objects.
   */
  protected $outboundProcessors = [];

  /**
   * Holds the array of outbound processors, sorted by priority.
   *
   * @var array
   *   An array of path processor objects.
   */
  protected $sortedOutbound = [];

  /**
   * Adds an inbound processor object to the $inboundProcessors property.
   *
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $processor
   *   The processor object to add.
   * @param int $priority
   *   The priority of the processor being added.
   */
  public function addInbound(InboundPathProcessorInterface $processor, $priority = 0) {
    $this->inboundProcessors[$priority][] = $processor;
    $this->sortedInbound = [];
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $processors = $this->getInbound();
    foreach ($processors as $processor) {
      $path = $processor->processInbound($path, $request);
    }
    return $path;
  }

  /**
   * Returns the sorted array of inbound processors.
   *
   * @return array
   *   An array of processor objects.
   */
  protected function getInbound() {
    if (empty($this->sortedInbound)) {
      $this->sortedInbound = $this->sortProcessors('inboundProcessors');
    }

    return $this->sortedInbound;
  }

  /**
   * Adds an outbound processor object to the $outboundProcessors property.
   *
   * @param \Drupal\Core\PathProcessor\OutboundPathProcessorInterface $processor
   *   The processor object to add.
   * @param int $priority
   *   The priority of the processor being added.
   */
  public function addOutbound(OutboundPathProcessorInterface $processor, $priority = 0) {
    $this->outboundProcessors[$priority][] = $processor;
    $this->sortedOutbound = [];
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $processors = $this->getOutbound();
    foreach ($processors as $processor) {
      $path = $processor->processOutbound($path, $options, $request, $bubbleable_metadata);
    }
    return $path;
  }

  /**
   * Returns the sorted array of outbound processors.
   *
   * @return array
   *   An array of processor objects.
   */
  protected function getOutbound() {
    if (empty($this->sortedOutbound)) {
      $this->sortedOutbound = $this->sortProcessors('outboundProcessors');
    }

    return $this->sortedOutbound;
  }

  /**
   * Sorts the processors according to priority.
   *
   * @param string $type
   *   The processor type to sort, e.g. 'inboundProcessors'.
   */
  protected function sortProcessors($type) {
    $sorted = [];
    krsort($this->{$type});

    foreach ($this->{$type} as $processors) {
      $sorted = array_merge($sorted, $processors);
    }
    return $sorted;
  }

}
