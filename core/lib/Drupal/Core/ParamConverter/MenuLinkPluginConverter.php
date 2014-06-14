<?php

/**
 * @file
 * Contains \Drupal\Core\ParamConverter\MenuLinkPluginConverter.
 */

namespace Drupal\Core\ParamConverter;

use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting entity ids to full objects.
 */
class MenuLinkPluginConverter implements ParamConverterInterface {

  /**
   * Plugin manager which creates the instance from the value.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuManager;

  /**
   * Constructs a new MenuLinkPluginConverter.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_tree
   *   The menu link plugin manager.
   */
  public function __construct(MenuLinkManagerInterface $menu_manager) {
    $this->menuManager = $menu_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults, Request $request) {
    if ($value) {
      try {
        return $this->menuManager->createInstance($value);
      }
      catch (PluginException $e) {
        // Suppress the error.
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] === 'menu_link_plugin');
  }

}
