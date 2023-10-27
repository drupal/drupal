<?php

namespace Drupal\Component\Plugin\Attribute;

/**
 * Defines a Plugin attribute object that just contains an ID.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class PluginID extends AttributeBase {

  /**
   * {@inheritdoc}
   */
  public function get(): array {
    return [
      'id' => $this->getId(),
      'class' => $this->getClass(),
      'provider' => $this->getProvider(),
    ];
  }

}
