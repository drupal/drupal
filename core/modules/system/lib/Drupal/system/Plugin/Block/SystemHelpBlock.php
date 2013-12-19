<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemHelpBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a 'System Help' block.
 *
 * @Block(
 *   id = "system_help_block",
 *   admin_label = @Translation("System Help")
 * )
 */
class SystemHelpBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Stores the help text associated with the active menu item.
   *
   * @var string
   */
  protected $help;

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
   * Creates a SystemHelpBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Request $request, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->request = $request;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition, $container->get('request'), $container->get('module_handler'));
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $this->help = $this->getActiveHelp($this->request);
    return (bool) $this->help;
  }

  /**
   * Returns the help associated with the active menu item.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  protected function getActiveHelp(Request $request) {
    $output = '';
    $router_path = $request->attributes->get('_system_path');
    // We will always have a path unless we are on a 403 or 404.
    if (!$router_path) {
      return '';
    }

    $arg = drupal_help_arg(explode('/', $router_path));

    foreach ($this->moduleHandler->getImplementations('help') as $module) {
      $function = $module . '_help';
      // Lookup help for this path.
      if ($help = $function($router_path, $arg)) {
        $output .= $help . "\n";
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#children' => $this->help,
    );
  }

}
