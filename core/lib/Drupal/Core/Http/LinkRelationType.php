<?php

namespace Drupal\Core\Http;

use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a single link relationship type.
 */
class LinkRelationType extends PluginBase implements LinkRelationTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function isRegistered() {
    return !$this->isExtension();
  }

  /**
   * {@inheritdoc}
   */
  public function isExtension() {
    return isset($this->pluginDefinition['uri']);
  }

  /**
   * {@inheritdoc}
   */
  public function getRegisteredName() {
    return $this->isRegistered() ? $this->getPluginId() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionUri() {
    return $this->isExtension() ? $this->pluginDefinition['uri'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getReference() {
    return $this->pluginDefinition['reference'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getNotes() {
    return $this->pluginDefinition['notes'] ?? '';
  }

}
