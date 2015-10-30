<?php

/**
 * @file
 * Contains \Drupal\help\Plugin\Block\HelpBlock.
 */

namespace Drupal\help\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a 'Help' block.
 *
 * @Block(
 *   id = "help_block",
 *   admin_label = @Translation("Help")
 * )
 */
class HelpBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Creates a HelpBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request, ModuleHandlerInterface $module_handler, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->request = $request;
    $this->moduleHandler = $module_handler;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('module_handler'),
      $container->get('current_route_match')
    );
  }

  /**
   * Returns the help associated with the active menu item.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  protected function getActiveHelp(Request $request) {
    // Do not show on a 403 or 404 page.
    if ($request->attributes->has('exception')) {
      return '';
    }

    $help = $this->moduleHandler->invokeAll('help', array($this->routeMatch->getRouteName(), $this->routeMatch));
    return $help ? implode("\n", $help) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $help = $this->getActiveHelp($this->request);
    if (!$help) {
      return [];
    }
    else {
      return [
        '#children' => $help,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // The "Help" block must be cached per URL: help is defined for a
    // given path, and does not come with any access restrictions.
    return Cache::mergeContexts(parent::getCacheContexts(), ['url']);
  }

}
