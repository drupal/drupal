<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Collects and validates input values for a recipe.
 *
 * @internal
 *   This API is experimental.
 */
final class InputConfigurator {

  /**
   * The input data.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface[]
   */
  private array $data = [];

  /**
   * The collected input values.
   *
   * @var mixed[]
   */
  private array $values = [];

  /**
   * @param array<string, array<string, mixed>> $definitions
   *   The recipe's input definitions, keyed by name. This is an array of arrays
   *   where each sub-array has, at minimum:
   *   - `description`: A short, human-readable description of the input (e.g.,
   *      what the recipe uses it for).
   *   - `data_type`: A primitive data type known to the typed data system.
   *   - `constraints`: An optional array of validation constraints to apply
   *     to the value. This should be an associative array of arrays, keyed by
   *     constraint name, where each sub-array is a set of options for that
   *     constraint (identical to the way validation constraints are defined in
   *     config schema).
   *   - `default`: A default value for the input, if it cannot be collected
   *     the user. See ::getDefaultValue() for more information.
   * @param \Drupal\Core\Recipe\RecipeConfigurator $dependencies
   *   The recipes that this recipe depends on.
   * @param string $prefix
   *   A prefix for each input definition, to give each one a unique name
   *   when collecting input for multiple recipes. Usually this is the unique
   *   name of the recipe.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   *   The typed data manager service.
   */
  public function __construct(
    array $definitions,
    private readonly RecipeConfigurator $dependencies,
    private readonly string $prefix,
    TypedDataManagerInterface $typedDataManager,
  ) {
    // Convert the input definitions to typed data definitions.
    foreach ($definitions as $name => $definition) {
      $data_definition = DataDefinition::create($definition['data_type'])
        ->setDescription($definition['description'])
        ->setConstraints($definition['constraints'] ?? []);

      unset(
        $definition['data_type'],
        $definition['description'],
        $definition['constraints'],
      );
      $data_definition->setSettings($definition);
      $this->data[$name] = $typedDataManager->create($data_definition, name: "$prefix.$name");
    }
  }

  /**
   * Returns the typed data definitions for the inputs defined by this recipe.
   *
   * This does NOT return the data definitions for inputs defined by this
   * recipe's dependencies.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   The typed data definitions, keyed by input name.
   */
  public function getDataDefinitions(): array {
    return array_map(fn (TypedDataInterface $data) => $data->getDataDefinition(), $this->data);
  }

  /**
   * Returns the collected input values, keyed by name.
   *
   * @return mixed[]
   *   The collected input values, keyed by name.
   */
  public function getValues(): array {
    return $this->values;
  }

  /**
   * Returns the description for all inputs of this recipe and its dependencies.
   *
   * @return string[]
   *   The descriptions of every input defined by the recipe and its
   *   dependencies, keyed by the input's fully qualified name (i.e., prefixed
   *   by the name of the recipe that defines it).
   */
  public function describeAll(): array {
    $descriptions = [];
    foreach ($this->dependencies->recipes as $dependency) {
      $descriptions = array_merge($descriptions, $dependency->input->describeAll());
    }
    foreach ($this->data as $data) {
      $name = $data->getName();
      $descriptions[$name] = $data->getDataDefinition()->getDescription();
    }
    return $descriptions;
  }

  /**
   * Collects input values for this recipe and its dependencies.
   *
   * @param \Drupal\Core\Recipe\InputCollectorInterface $collector
   *   The input collector to use.
   * @param string[] $processed
   *   The names of the recipes for which input has already been collected.
   *   Internal use only, should not be passed in by calling code.
   *
   * @throws \Symfony\Component\Validator\Exception\ValidationFailedException
   *   Thrown if any of the collected values violate their validation
   *   constraints.
   * @throws \LogicException
   *   Thrown if input values have already been collected for this recipe.
   */
  public function collectAll(InputCollectorInterface $collector, array &$processed = []): void {
    // Don't bother collecting values for a recipe we've already seen.
    if (in_array($this->prefix, $processed, TRUE)) {
      return;
    }
    if ($this->values) {
      throw new \LogicException('Input values cannot be changed once they have been set.');
    }
    // First, collect values for the recipe's dependencies.
    /** @var \Drupal\Core\Recipe\Recipe $dependency */
    foreach ($this->dependencies->recipes as $dependency) {
      $dependency->input->collectAll($collector, $processed);
    }

    foreach ($this->data as $key => $data) {
      $definition = $data->getDataDefinition();

      $value = $collector->collectValue(
        $data->getName(),
        $definition,
        $this->getDefaultValue($definition),
      );
      $data->setValue($value, FALSE);

      $violations = $data->validate();
      if (count($violations) > 0) {
        throw new ValidationFailedException($data, $violations);
      }
      $this->values[$key] = $data->getCastedValue();
    }
    $processed[] = $this->prefix;
  }

  /**
   * Returns the default value for an input definition.
   *
   * @param array $definition
   *   An input definition. Must contain a `source` element, which can be either
   *   'config' or 'value'. If `source` is 'config', then there must also be a
   *   `config` element, which is a two-element indexed array containing
   *   (in order) the name of an extant config object, and a property path
   *   within that object. If `source` is 'value', then there must be a `value`
   *   element, which will be returned as-is.
   *
   * @return mixed
   *   The default value.
   */
  private function getDefaultValue(DataDefinition $definition): mixed {
    $settings = $definition->getSetting('default');

    if ($settings['source'] === 'config') {
      [$name, $key] = $settings['config'];
      $config = \Drupal::config($name);
      if ($config->isNew()) {
        throw new \RuntimeException("The '$name' config object does not exist.");
      }
      return $config->get($key);
    }
    return $settings['value'];
  }

}
