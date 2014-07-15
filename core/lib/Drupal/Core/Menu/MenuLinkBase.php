<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLinkBase.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\String;
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
  public function isResetable() {
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
    throw new PluginException(String::format('Menu link plugin with ID @id does not support deletion', array('@id' => $this->getPluginId())));
  }

}
