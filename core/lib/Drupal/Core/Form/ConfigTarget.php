<?php

declare(strict_types = 1);

namespace Drupal\Core\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Config;

/**
 * Represents the mapping of a config property to a form element.
 *
 * @see \Drupal\Core\Form\ToConfig
 */
final class ConfigTarget {

  /**
   * The parents of the form element which maps to this config property.
   *
   * @var array
   *
   * @see \Drupal\Core\Form\ConfigFormBase::storeConfigKeyToFormElementMap()
   *
   * @internal
   *   This property is for internal use only.
   */
  public array $elementParents;

  /**
   * The property paths to target.
   *
   * @var string[]
   */
  public readonly array $propertyPaths;

  /**
   * Transforms a value loaded from config before it gets displayed by the form.
   *
   * @var callable|null
   *
   * @see ::getValue()
   */
  public readonly mixed $fromConfig;

  /**
   * Transforms a value submitted by the form before it is set in the config.
   *
   * @var callable|null
   *
   * @see ::setValue()
   */
  public readonly mixed $toConfig;

  /**
   * Constructs a ConfigTarget object.
   *
   * @param string $configName
   *   The name of the config object being read from or written to, e.g.
   *   `system.site`.
   * @param string|array $propertyPath
   *   The property path(s) being read or written, e.g., `page.front`.
   * @param callable|null $fromConfig
   *   (optional) A callback which should transform the value loaded from
   *   config before it gets displayed by the form. If NULL, no transformation
   *   will be done. The callback will receive all of the values loaded from
   *   config as separate arguments, in the order specified by
   *   $this->propertyPaths. Defaults to NULL.
   * @param callable|null $toConfig
   *   (optional) A callback which should transform the value submitted by the
   *   form before it is set in the config object. If NULL, no transformation
   *   will be done. The callback will receive the value submitted through the
   *   form; if this object is targeting multiple property paths, the value will
   *   be an array of the submitted values, keyed by property path, and must
   *   return an array with the transformed values, also keyed by property path.
   *   The callback will receive the form state object as its second argument.
   *   The callback may return a special values:
   *   - ToConfig::NoMapping, to indicate that the given form value does not
   *     need to be mapped onto the Config object
   *   - ToConfig::DeleteKey to indicate that the targeted property path should
   *     be deleted from config.
   *   Defaults to NULL.
   */
  public function __construct(
    public readonly string $configName,
    string|array $propertyPath,
    ?callable $fromConfig = NULL,
    ?callable $toConfig = NULL,
  ) {
    $this->fromConfig = $fromConfig;
    $this->toConfig = $toConfig;

    if (is_string($propertyPath)) {
      $propertyPath = [$propertyPath];
    }
    elseif (count($propertyPath) > 1 && (empty($fromConfig) || empty($toConfig))) {
      throw new \LogicException('The $fromConfig and $toConfig arguments must be passed to ' . __METHOD__ . '() if multiple property paths are targeted.');
    }
    $this->propertyPaths = array_values($propertyPath);
  }

  /**
   * Creates a ConfigTarget object.
   *
   * @param string $target
   *   The name of the config object, and property path, being read from or
   *   written to, in the form `CONFIG_NAME:PROPERTY_PATH`. For example,
   *   `system.site:page.front`.
   * @param string|null $fromConfig
   *   (optional) A callback which should transform the value loaded from
   *   config before it gets displayed by the form. If NULL, no transformation
   *   will be done. The callback will receive all of the values loaded from
   *   config as separate arguments, in the order specified by
   *   $this->propertyPaths. Defaults to NULL.
   * @param string|null $toConfig
   *   (optional) A callback which should transform the value submitted by the
   *   form before it is set in the config object. If NULL, no transformation
   *   will be done. The callback will receive the value submitted through the
   *   form; if this object is targeting multiple property paths, the value will
   *   be an array of the submitted values, keyed by property path, and must
   *   return an array with the transformed values, also keyed by property path.
   *   The callback will receive the form state object as its second argument.
   *   Defaults to NULL.
   *
   * @return self
   *   A ConfigTarget instance.
   */
  public static function fromString(string $target, ?string $fromConfig = NULL, ?string $toConfig = NULL): self {
    [$configName, $propertyPath] = explode(':', $target, 2);
    return new self($configName, $propertyPath, $fromConfig, $toConfig);
  }

  /**
   * Gets the config target object for an element from a form array.
   *
   * @param array $array_parents
   *   The array to locate the element in the form.
   * @param array $form
   *   The form array.
   *
   * @return self
   *   A ConfigTarget instance.
   */
  public static function fromForm(array $array_parents, array $form): self {
    $element = NestedArray::getValue($form, $array_parents);
    if (!isset($element['#config_target'])) {
      throw new \LogicException('The form element [' . implode('][', $array_parents) . '] does not have the #config_target property set');
    }
    $target = $element['#config_target'];
    if (is_string($target)) {
      $target = ConfigTarget::fromString($target);
    }
    if (!$target instanceof ConfigTarget) {
      throw new \LogicException('The form element [' . implode('][', $array_parents) . '] #config_target property is not a string or a ConfigTarget object');
    }

    // Add the element information to the config target object.
    $target->elementParents = $element['#parents'];
    return $target;
  }

  /**
   * Retrieves the mapped value from config.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The config object we're reading from.
   *
   * @return mixed
   *   The mapped value, with any transformations applied.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the given config object is not the one being targeted by
   *   $this->configName.
   */
  public function getValue(Config $config): mixed {
    if ($config->getName() !== $this->configName) {
      throw new \InvalidArgumentException(sprintf('Config target is associated with %s but %s given.', $this->configName, $config->getName()));
    }

    $is_multi_target = $this->isMultiTarget();

    $value = $is_multi_target
      ? array_map($config->get(...), $this->propertyPaths)
      : $config->get($this->propertyPaths[0]);

    if ($this->fromConfig) {
      $value = $is_multi_target
        ? ($this->fromConfig)(...$value)
        : ($this->fromConfig)($value);
    }
    return $value;
  }

  /**
   * Sets the submitted value from config.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The config object we're changing.
   * @param mixed $value
   *   The value(s) to set. If this object is targeting multiple property paths,
   *   this must be an array with the values to set, keyed by property path.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the given config object is not the one being targeted by
   *   $this->configName.
   * @throws \LogicException
   *   Thrown if this object is targeting multiple property paths and $value
   *   does not contain a value for every targeted property path.
   */
  public function setValue(Config $config, mixed $value, FormStateInterface $form_state): void {
    if ($config->getName() !== $this->configName) {
      throw new \InvalidArgumentException(sprintf('Config target is associated with %s but %s given.', $this->configName, $config->getName()));
    }

    $is_multi_target = $this->isMultiTarget();
    if ($this->toConfig) {
      $value = ($this->toConfig)($value, $form_state);
      if ($is_multi_target) {
        // If we're targeting multiple property paths, $value needs to be an array
        // with every targeted property path.
        if (!is_array($value)) {
          throw new \LogicException(sprintf('The toConfig callable returned a %s, but it must be an array with a key-value pair for each of the targeted property paths.', gettype($value)));
        }
        elseif ($missing_keys = array_diff($this->propertyPaths, array_keys($value))) {
          throw new \LogicException(sprintf('The toConfig callable returned an array that is missing key-value pairs for the following targeted property paths: %s.', implode(', ', $missing_keys)));
        }
        elseif ($unknown_keys = array_diff(array_keys($value), $this->propertyPaths)) {
          throw new \LogicException(sprintf('The toConfig callable returned an array that contains key-value pairs that do not match targeted property paths: %s.', implode(', ', $unknown_keys)));
        }
      }
    }

    // Match the structure expected for a multi-target ConfigTarget.
    if (!$is_multi_target) {
      $value = [$this->propertyPaths[0] => $value];
    }

    // Set the returned value, or if a special value (one of the cases in the
    // ConfigTargetValue enum): apply the appropriate action.
    array_walk($value, fn (mixed $value, string $property) => match ($value) {
      // No-op.
      ToConfig::NoOp => NULL,
      // Delete.
      ToConfig::DeleteKey => $config->clear($property),
      // Set.
      default => $config->set($property, $value),
    });
  }

  /**
   * Indicates if this object targets multiple property paths.
   *
   * @return bool
   *   TRUE if this object is targeting multiple property paths, otherwise
   *   FALSE.
   */
  private function isMultiTarget(): bool {
    return count($this->propertyPaths) > 1;
  }

}
