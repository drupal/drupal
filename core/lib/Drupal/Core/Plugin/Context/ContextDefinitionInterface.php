<?php

namespace Drupal\Core\Plugin\Context;

use Drupal\Component\Plugin\Context\ContextDefinitionInterface as ComponentContextDefinitionInterface;

/**
 * Interface for context definitions.
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
