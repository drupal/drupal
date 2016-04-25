<?php

namespace Drupal\menu_test\Plugin\Menu\LocalAction;

use Drupal\Core\Config\Config;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a test local action plugin class.
 */
class TestLocalActionWithConfig extends LocalActionDefault {

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->config->get('title');
  }

  /**
   * Constructs a TestLocalActionWithConfig object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Config\Config $config
   *   The 'menu_test.links.action' config.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, Config $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider);

    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider'),
      $container->get('config.factory')->get('menu_test.links.action')
    );
  }

}
