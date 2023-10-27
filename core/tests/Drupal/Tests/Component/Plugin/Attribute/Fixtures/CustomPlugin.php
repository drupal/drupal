<?php

namespace com\example\PluginNamespace;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Custom plugin attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class CustomPlugin extends Plugin {

  /**
   * Constructs a CustomPlugin attribute object.
   *
   * @param string $id
   *   The attribute class ID.
   * @param string $title
   *   The title.
   */
  public function __construct(
    public readonly string $id,
    public readonly string $title
  ) {}

}

/**
 * Custom plugin attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class CustomPlugin2 extends Plugin {}
