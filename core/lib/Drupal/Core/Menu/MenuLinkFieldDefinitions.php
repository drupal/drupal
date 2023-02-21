<?php

namespace Drupal\Core\Menu;

/**
 * Provides base field definitions for an entity type.
 *
 * @package Drupal\Core\Menu
 */
trait MenuLinkFieldDefinitions {

  /**
   * Provides some default values for the definition of all menu link plugins.
   *
   * @var array
   */
  protected $defaults = [
    // (required) The name of the menu for this link.
    'menu_name' => 'tools',
    // (required) The name of the route this links to, unless it's external.
    'route_name' => '',
    // Parameters for route variables when generating a link.
    'route_parameters' => [],
    // The external URL if this link has one (required if route_name is empty).
    'url' => '',
    // The static title for the menu link. If this came from a YAML definition
    // or other safe source this may be a TranslatableMarkup object.
    'title' => '',
    // The description. If this came from a YAML definition or other safe source
    // this may be be a TranslatableMarkup object.
    'description' => '',
    // The plugin ID of the parent link (or NULL for a top-level link).
    'parent' => '',
    // The weight of the link.
    'weight' => 0,
    // The default link options.
    'options' => [],
    'expanded' => 0,
    'enabled' => 1,
    // The name of the module providing this link.
    'provider' => '',
    'metadata' => [],
    // Default class for local task implementations.
    'class' => 'Drupal\Core\Menu\MenuLinkDefault',
    'form_class' => 'Drupal\Core\Menu\Form\MenuLinkDefaultForm',
    // The plugin ID. Set by the plugin system based on the top-level YAML key.
    'id' => '',
  ];

}
