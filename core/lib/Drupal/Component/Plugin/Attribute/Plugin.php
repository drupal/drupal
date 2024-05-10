<?php

namespace Drupal\Component\Plugin\Attribute;

/**
 * Defines a Plugin attribute object.
 *
 * Attributes in plugin classes can use this class in order to pass various
 * metadata about the plugin through the parser to
 * DiscoveryInterface::getDefinitions() calls.
 *
 * @ingroup plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Plugin extends AttributeBase {

  /**
   * Constructs a plugin attribute object.
   *
   * @param string $id
   *   The attribute class ID.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?string $deriver = NULL,
  ) {}

}
