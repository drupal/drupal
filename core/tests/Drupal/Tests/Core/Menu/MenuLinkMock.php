<?php

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\MenuLinkBase;

/**
 * Defines a mock implementation of a menu link used in tests only.
 */
class MenuLinkMock extends MenuLinkBase {

  protected static $defaults = [
    'menu_name' => 'mock',
    'route_name' => 'MUST BE PROVIDED',
    'route_parameters' => [],
    'url' => '',
    'title' => 'MUST BE PROVIDED',
    'title_arguments' => [],
    'title_context' => '',
    'description' => '',
    'parent' => 'MUST BE PROVIDED',
    'weight' => '0',
    'options' => [],
    'expanded' => '0',
    'enabled' => '1',
    'provider' => 'test',
    'metadata' => [
      'cache_contexts' => [],
      'cache_tags' => [],
      'cache_max_age' => Cache::PERMANENT,
    ],
    'class' => 'Drupal\\Tests\\Core\Menu\\MenuLinkMock',
    'form_class' => 'Drupal\\Core\\Menu\\Form\\MenuLinkDefaultForm',
    'id' => 'MUST BE PROVIDED',
  ];

  /**
   * Create an instance from a definition with at least id, title, route_name.
   */
  public static function create($definition) {
    return new static([], $definition['id'], $definition + static::$defaults);
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

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->pluginDefinition['metadata']['cache_contexts'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->pluginDefinition['metadata']['cache_tags'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->pluginDefinition['metadata']['cache_max_age'];
  }

}
