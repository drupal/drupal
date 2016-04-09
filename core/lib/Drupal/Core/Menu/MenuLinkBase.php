<?php

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;

/**
 * Defines a base menu link class.
 */
abstract class MenuLinkBase extends PluginBase implements MenuLinkInterface {

  /**
   * The list of definition values where an override is allowed.
   *
   * The keys are definition names. The values are ignored.
   *
   * @var array
   */
  protected $overrideAllowed = array();

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    // By default the weight is 0.
    if (!isset($this->pluginDefinition['weight'])) {
      $this->pluginDefinition['weight'] = 0;
    }
    return $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuName() {
    return $this->pluginDefinition['menu_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->pluginDefinition['provider'];
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return $this->pluginDefinition['parent'];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->pluginDefinition['enabled'];
  }

  /**
   * {@inheritdoc}
   */
  public function isExpanded() {
    return (bool) $this->pluginDefinition['expanded'];
  }

  /**
   * {@inheritdoc}
   */
  public function isResettable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return (bool) $this->getTranslateRoute();
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    return (bool) $this->getDeleteRoute();
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->pluginDefinition['options'] ?: array();
  }

  /**
   * {@inheritdoc}
   */
  public function getMetaData() {
    return $this->pluginDefinition['metadata'] ?: array();
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return isset($this->pluginDefinition['route_name']) ? $this->pluginDefinition['route_name'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    return isset($this->pluginDefinition['route_parameters']) ? $this->pluginDefinition['route_parameters'] : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlObject($title_attribute = TRUE) {
    $options = $this->getOptions();
    if ($title_attribute && $description = $this->getDescription()) {
      $options['attributes']['title'] = $description;
    }
    if (empty($this->pluginDefinition['url'])) {
      return new Url($this->getRouteName(), $this->getRouteParameters(), $options);
    }
    else {
      return Url::fromUri($this->pluginDefinition['url'], $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormClass() {
    return $this->pluginDefinition['form_class'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRoute() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslateRoute() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink() {
    throw new PluginException("Menu link plugin with ID '{$this->getPluginId()}' does not support deletion");
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
