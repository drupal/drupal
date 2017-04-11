<?php

namespace Drupal\Core\Plugin\Context;

use Drupal\Component\Plugin\Context\ContextDefinitionInterface as ComponentContextDefinitionInterface;

/**
 * Interface to define definition objects in ContextInterface via TypedData.
 *
 * @see \Drupal\Component\Plugin\Context\ContextDefinitionInterface
 * @see \Drupal\Core\Plugin\Context\ContextInterface
 */
interface ContextDefinitionInterface extends ComponentContextDefinitionInterface {

  /**
   * Returns the data definition of the defined context.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The data definition object.
   */
  public function getDataDefinition();

}
