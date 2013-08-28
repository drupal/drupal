<?php

/**
 * @file
 * Contains \Drupal\layout\Access\LayoutAccessCheck.
 */

namespace Drupal\layout\Access;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks layout access.
 */
class LayoutAccessCheck implements StaticAccessCheckInterface {

  /**
   * The layout manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $layoutManager;

  /**
   * Constructs a LayoutAccessCheck object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $layout_manager
   *   The layout manager.
   */
  public function __construct(PluginManagerInterface $layout_manager) {
    $this->layoutManager = $layout_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return '_access_layout_user';
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    return $this->layoutManager->getDefinition($request->attributes->get('key')) ? static::ALLOW : static::DENY;
  }

}
