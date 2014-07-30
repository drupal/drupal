<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Menu\MenuLinkMock
 */

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\MenuLinkBase;

/**
 * Defines a mock implementation of a menu link used in tests only.
 */
class MenuLinkMock extends MenuLinkBase {

  protected static $defaults = array(
    'menu_name' => 'mock',
    'route_name' => 'MUST BE PROVIDED',
    'route_parameters' => array(),
    'url' => '',
    'title' => 'MUST BE PROVIDED',
    'title_arguments' => array(),
    'title_context' => '',
    'description' => '',
    'parent' => 'MUST BE PROVIDED',
    'weight' => '0',
    'options' => array(),
    'expanded' => '0',
    'hidden' => '0',
    'provider' => 'simpletest',
    'metadata' => array(),
    'class' => 'Drupal\\Tests\\Core\Menu\\MenuLinkMock',
    'form_class' => 'Drupal\\Core\\Menu\\Form\\MenuLinkDefaultForm',
    'id' => 'MUST BE PROVIDED',
  );

  /**
   * Create an instance from a definition with at least id, title, route_name.
   */
  public static function create($definition) {
    return new static(array(), $definition['id'], $definition + static::$defaults);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if ($this->pluginDefinition['description']) {
      return $this->pluginDefinition['description'];
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    // No-op.
    return $this->pluginDefinition;
  }

}
