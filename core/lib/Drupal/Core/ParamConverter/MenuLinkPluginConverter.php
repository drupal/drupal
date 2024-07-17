<?php

namespace Drupal\Core\ParamConverter;

use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Component\Plugin\Exception\PluginException;
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
  protected $menuLinkManager;

  /**
   * Constructs a new MenuLinkPluginConverter.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link plugin manager.
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager) {
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if ($value) {
      try {
        return $this->menuLinkManager->createInstance($value);
      }
      catch (PluginException) {
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
