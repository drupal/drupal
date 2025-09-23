<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Collects user-provided input values for recipes.
 *
 * Implementations of this interface are responsible for obtaining values
 * required by recipes at runtime. This allows recipes to request dynamic
 * information (for example, a site name or administrator email address) from
 * the user or another source, rather than hardcoding values.
 *
 * @see \Drupal\Core\Recipe\FormInputCollector
 * @see \Drupal\Core\Recipe\PredefinedInputCollector
 */
interface InputCollectorInterface {

  /**
   * Collects a single input value for a recipe.
   *
   * @param string $name
   *   The machine name of the input to collect, in the form
   *   RECIPE_NAME.INPUT_NAME.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition that describes the expected type, constraints, and
   *   metadata for the input value.
   * @param mixed $default_value
   *   The default value to return if no input is provided.
   *
   * @return mixed
   *   The collected input value that satisfies the provided definition.
   */
  public function collectValue(string $name, DataDefinitionInterface $definition, mixed $default_value): mixed;

}
