<?php

declare(strict_types = 1);

namespace Drupal\Core\Form;

use Drupal\Component\Utility\NestedArray;

/**
 * Represents the mapping of a config property to a form element.
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
   * Transforms a value loaded from config before it gets displayed by the form.
   *
   * @var \Closure|null
   */
  public readonly ?\Closure $fromConfig;

  /**
   * Transforms a value submitted by the form before it is set in the config.
   *
   * @var \Closure|null
   */
  public readonly ?\Closure $toConfig;

  /**
   * Constructs a ConfigTarget object.
   *
   * @param string $configName
   *   The name of the config object being read from or written to, e.g.
   *   `system.site`.
   * @param string $propertyPath
   *   The property path being read or written, e.g., `page.front`.
   * @param callable|null $fromConfig
   *   (optional) A callback which should transform the value loaded from
   *   config before it gets displayed by the form. If NULL, no transformation
   *   will be done. Defaults to NULL.
   * @param callable|null $toConfig
   *   (optional) A callback which should transform the value submitted by the
   *   form before it is set in the config object. If NULL, no transformation
   *   will be done. Defaults to NULL.
   */
  public function __construct(
    public readonly string $configName,
    public readonly string $propertyPath,
    ?callable $fromConfig = NULL,
    ?callable $toConfig = NULL,
  ) {
    $this->fromConfig = $fromConfig ? $fromConfig(...) : NULL;
    $this->toConfig = $toConfig ? $toConfig(...) : NULL;
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
   *   will be done. Defaults to NULL.
   * @param string|null $toConfig
   *   (optional) A callback which should transform the value submitted by the
   *   form before it is set in the config object. If NULL, no transformation
   *   will be done. Defaults to NULL.
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

}
