<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemHelpBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
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
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Creates a SystemHelpBlock instance.
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
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
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
    return array(
      '#children' => $this->help,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Modify the default max age for the System Help block: help text is static
    // for a given URL, except when a module is updated, in which case
    // update.php must be run, which clears all caches. Thus it's safe to cache
    // the output for this block forever on a per-URL basis.
    return array('cache' => array('max_age' => \Drupal\Core\Cache\Cache::PERMANENT));
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredCacheContexts() {
    // The "System Help" block must be cached per URL: help is defined for a
    // given path, and does not come with any access restrictions.
    return array('cache_context.url');
  }

}
