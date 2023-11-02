<?php

declare(strict_types = 1);

namespace Drupal\Core\Form;

/**
 * Represents the mapping of a config property to a form element.
 */
final class ConfigTarget {

  /**
   * The name of the form element which maps to this config property.
   *
   * @var string
   *
   * @see \Drupal\Core\Form\ConfigFormBase::storeConfigKeyToFormElementMap()
   *
   * @internal
   *   This property is for internal use only.
   */
  public string $elementName;

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
   * Constructs a ConfigTarget object.
   *
   * @param string $configName
   *   The name of the config object being read from or written to, e.g.
   *   `system.site`.
   * @param string $propertyPath
   *   The property path being read or written, e.g., `page.front`.
   * @param string|null $fromConfig
   *   (optional) A callback which should transform the value loaded from
   *   config before it gets displayed by the form. If NULL, no transformation
   *   will be done. Defaults to NULL.
   * @param string|null $toConfig
   *   (optional) A callback which should transform the value submitted by the
   *   form before it is set in the config object. If NULL, no transformation
   *   will be done. Defaults to NULL.
   */
  public function __construct(
    public readonly string $configName,
    public readonly string $propertyPath,
    public readonly ?string $fromConfig = NULL,
    public readonly ?string $toConfig = NULL,
  ) {
    // If they're passed at all, $fromConfig and $toConfig need to be string
    // callables in order to guarantee that this object can be serialized as
    // part of a larger form array. If these could be arrays, then they could be
    // in the form of [$object, 'method'], which would break serialization if
    // $object was not serializable. This is also why we don't type hint these
    // parameters as ?callable, since that would allow closures (which can't
    // be serialized).
    if ($fromConfig) {
      assert(is_callable($fromConfig));
    }
    if ($toConfig) {
      assert(is_callable($toConfig));
    }
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

}
