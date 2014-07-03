<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLinkBase.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Base class used for MenuLink plugins.
 */
abstract class MenuLinkBase extends PluginBase implements MenuLinkInterface {

  /**
   * Defines the list of definition values where an override is allowed.
   *
   * @var array
   */
  protected $overrideAllowed = array();

  /**
   * {@inheritdoc}
   */
  public function build($title_attribute = TRUE) {
    $options = $this->getOptions();
    $description = $this->getDescription();
    if ($title_attribute && $description) {
      $options['attributes']['title'] = $description;
    }
    $build = array(
      '#type' => 'link',
      '#route_name' => $this->pluginDefinition['route_name'],
      '#route_parameters' => $this->pluginDefinition['route_parameters'],
      '#title' => $this->getTitle(),
      '#options' => $options,
    );
    return $build;
  }

  /**
   * Returns the weight of the menu link.
   *
   * @return int
   *   The weight of the menu link, 0 by default.
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
  public function getTitle() {
    // Subclasses may pull in the request or specific attributes as parameters.
    $options = array();
    if (!empty($this->pluginDefinition['title_context'])) {
      $options['context'] = $this->pluginDefinition['title_context'];
    }
    $args = array();
    if (isset($this->pluginDefinition['title_arguments']) && $title_arguments = $this->pluginDefinition['title_arguments']) {
      $args = (array) $title_arguments;
    }
    return $this->t($this->pluginDefinition['title'], $args, $options);
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
  public function isHidden() {
    return (bool) $this->pluginDefinition['hidden'];
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
  public function isDiscovered() {
    return (bool) $this->pluginDefinition['discovered'];
  }

  /**
   * {@inheritdoc}
   */
  public function isResetable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return FALSE;
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
  public function getDescription() {
    if ($this->pluginDefinition['description']) {
      return $this->t($this->pluginDefinition['description']);
    }
    return '';
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
  public function isCacheable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlObject($title_attribute = TRUE) {
    $options = $this->getOptions();
    $description = $this->getDescription();
    if ($title_attribute && $description) {
      $options['attributes']['title'] = $description;
    }
    if (empty($this->pluginDefinition['url'])) {
      return new Url($this->pluginDefinition['route_name'], $this->pluginDefinition['route_parameters'], $options);
    }
    else {
      $url = Url::createFromPath($this->pluginDefinition['url']);
      $url->setOptions($options);
      return $url;
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
    throw new PluginException(sprintf("Menu link plugin with ID %s does not support deletion", $this->getPluginId()));
  }

}
