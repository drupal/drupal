<?php

namespace Drupal\update_script_test\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Example path processor which breaks on inbound.
 */
class BrokenInboundPathProcessor implements InboundPathProcessorInterface {

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new BrokenInboundPathProcessor instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if ($this->state->get('update_script_test_broken_inbound', FALSE)) {
      throw new \RuntimeException();
    }
    else {
      return $path;
    }
  }

}
