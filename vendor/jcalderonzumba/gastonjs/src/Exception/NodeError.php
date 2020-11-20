<?php

namespace Zumba\GastonJS\Exception;

/**
 * Class NodeError
 * @package Zumba\GastonJS\Exception
 */
class NodeError extends ClientError {
  protected $node;

  /**
   * @param mixed $node
   * @param mixed $response
   */
  public function __construct($node, $response) {
    $this->node = $node;
    parent::__construct($response);
  }
}
