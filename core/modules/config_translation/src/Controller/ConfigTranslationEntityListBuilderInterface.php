<?php

namespace Drupal\config_translation\Controller;

use Drupal\Core\Entity\EntityListBuilderInterface;

/**
 * Defines an interface for configuration translation entity list builders.
 */
interface ConfigTranslationEntityListBuilderInterface extends EntityListBuilderInterface {

  /**
   * Sorts an array by value.
   *
   * @param array $a
   *   First item for comparison.
   * @param array $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   */
  public function sortRows($a, $b);

  /**
   * Sets the config translation mapper definition.
   *
   * @param mixed $mapper_definition
   *   The plugin definition of the config translation mapper.
   *
   * @return $this
   */
  public function setMapperDefinition($mapper_definition);

}
