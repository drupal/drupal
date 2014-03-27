<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\ContextualLinkManager.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Access\AccessManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a contextual link plugin manager to deal with contextual links.
 *
 * @see \Drupal\Core\Menu\ContextualLinkInterface
 */
class ContextualLinkManager extends DefaultPluginManager implements ContextualLinkManagerInterface {

  /**
   * Provides default values for a contextual link definition.
   *
   * @var array
   */
  protected $defaults = array(
    // (required) The name of the route to link to.
    'route_name' => '',
    // (required) The contextual links group.
    'group' => '',
    // The static title text for the link.
    'title' => '',
    // The default link options.
    'options' => array(),
    // The weight of the link.
    'weight' => NULL,
    // Default class for contextual link implementations.
    'class' => '\Drupal\Core\Menu\ContextualLinkDefault',
    // The plugin id. Set by the plugin system based on the top-level YAML key.
    'id' => '',
  );

  /**
   * A controller resolver object.
   *
   * @var \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * A static cache of all the contextual link plugins by group name.
   *
   * @var array
   */
  protected $pluginsByGroup;

  /**
   * Constructs a new ContextualLinkManager instance.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Access\AccessManager $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend, LanguageManager $language_manager, AccessManager $access_manager, AccountInterface $account, RequestStack $request_stack) {
    $this->discovery = new YamlDiscovery('contextual_links', $module_handler->getModuleDirectories());
    $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    $this->factory = new ContainerFactory($this);

    $this->controllerResolver = $controller_resolver;
    $this->accessManager = $access_manager;
    $this->account = $account;
    $this->moduleHandler = $module_handler;
    $this->requestStack = $request_stack;
    $this->alterInfo('contextual_links_plugins');
    $this->setCacheBackend($cache_backend, $language_manager, 'contextual_links_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

     // If there is no route name, this is a broken definition.
    if (empty($definition['route_name'])) {
      throw new PluginException(sprintf('Contextual link plugin (%s) definition must include "route_name".', $plugin_id));
    }
     // If there is no group name, this is a broken definition.
    if (empty($definition['group'])) {
      throw new PluginException(sprintf('Contextual link plugin (%s) definition must include "group".', $plugin_id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getContextualLinkPluginsByGroup($group_name) {
    if (isset($this->pluginsByGroup[$group_name])) {
      $contextual_links = $this->pluginsByGroup[$group_name];
    }
    elseif ($cache = $this->cacheBackend->get($this->cacheKey . ':' . $group_name)) {
      $contextual_links = $cache->data;
      $this->pluginsByGroup[$group_name] = $contextual_links;
    }
    else {
      $contextual_links = array();
      foreach ($this->getDefinitions() as $plugin_id => $plugin_definition) {
        if ($plugin_definition['group'] == $group_name) {
          $contextual_links[$plugin_id] = $plugin_definition;
        }
      }
      $this->cacheBackend->set($this->cacheKey . ':' . $group_name, $contextual_links);
      $this->pluginsByGroup[$group_name] = $contextual_links;
    }
    return $contextual_links;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextualLinksArrayByGroup($group_name, array $route_parameters, array $metadata = array()) {
    $links = array();
    $request = $this->requestStack->getCurrentRequest();
    foreach ($this->getContextualLinkPluginsByGroup($group_name) as $plugin_id => $plugin_definition) {
      /** @var $plugin \Drupal\Core\Menu\ContextualLinkInterface */
      $plugin = $this->createInstance($plugin_id);
      $route_name = $plugin->getRouteName();

      // Check access.
      if (!$this->accessManager->checkNamedRoute($route_name, $route_parameters, $this->account)) {
        continue;
      }

      $links[$plugin_id] = array(
        'route_name' => $route_name,
        'route_parameters' => $route_parameters,
        'title' => $plugin->getTitle($request),
        'weight' => $plugin->getWeight(),
        'localized_options' => $plugin->getOptions(),
        'metadata' => $metadata,
      );
    }

    $this->moduleHandler->alter('contextual_links', $links, $group_name, $route_parameters);

    return $links;
  }

}
