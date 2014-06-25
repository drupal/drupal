<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Context\ContextInterface.
 */

namespace Drupal\Core\Plugin\Context;

use Drupal\Component\Plugin\Context\ContextInterface as ComponentContextInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Interface for context.
 */
interface ContextInterface extends ComponentContextInterface {

  /**
   * Gets the context value as typed data object.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   */
  public function getContextData();

  /**
   * Sets the context value as typed data object.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $data
   *   The context value as a typed data object.
   *
   * @return $this
   */
  public function setContextData(TypedDataInterface $data);

}
