<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLinkManager.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\String;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;


/**
 * Manages discovery, instantiation, and tree building of menu link plugins.
 *
 * This manager finds plugins that are rendered as menu links.
 */
class MenuLinkManager implements MenuLinkManagerInterface {

  /**
   * {@inheritdoc}
   */
  protected $defaults = array(
    // (required) The name of the menu for this link.
    'menu_name' => 'tools',
    // (required) The name of the route this links to, unless it's external.
    'route_name' => '',
    // Parameters for route variables when generating a link.
    'route_parameters' => array(),
    // The external URL if this link has one (required if route_name is empty).
    'url' => '',
    // The static title for the menu link.
    'title' => '',
    'title_arguments' => array(),
    'title_context' => '',
    // The description.
    'description' => '',
    // The plugin ID of the parent link (or NULL for a top-level link).
    'parent' => '',
    // The weight of the link.
    'weight' => 0,
    // The default link options.
    'options' => array(),
    'expanded' => 0,
    'hidden' => 0,
    // Flag for whether this plugin was discovered. Should be set to 0 or NULL
    // for definitions that are added via a direct save.
    'discovered' => 0,
    'provider' => '',
    'metadata' => array(),
    // Default class for local task implementations.
    'class' => 'Drupal\Core\Menu\MenuLinkDefault',
    'form_class' => 'Drupal\Core\Menu\Form\MenuLinkDefaultForm',
    // The plugin id. Set by the plugin system based on the top-level YAML key.
    'id' => '',
  );

  /**
   * The object that discovers plugins managed by this manager.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $discovery;

  /**
   * The object that instantiates plugins managed by this manager.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface
   */
  protected $factory;

  /**
   * The menu link tree storage.
   *
   * @var \Drupal\Core\Menu\MenuTreeStorageInterface
   */
  protected $treeStorage;

  /**
   * Service providing overrides for static links
   *
   * @var \Drupal\Core\Menu\StaticMenuLinkOverridesInterface
   */
  protected $overrides;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  /**
   * Constructs a \Drupal\Core\Menu\MenuLinkTree object.
   *
   * @param \Drupal\Core\Menu\MenuTreeStorageInterface $tree_storage
   *   The menu link tree storage.
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $overrides
   *   Service providing overrides for static links
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(MenuTreeStorageInterface $tree_storage, StaticMenuLinkOverridesInterface $overrides, ModuleHandlerInterface $module_handler) {
    $this->treeStorage = $tree_storage;
    $this->overrides = $overrides;
    $this->factory = new ContainerFactory($this);
    $this->moduleHandler = $module_handler;
  }

  /**
   * Performs extra processing on plugin definitions.
   *
   * By default we add defaults for the type to the definition. If a type has
   * additional processing logic they can do that by replacing or extending the
   * method.
   */
  protected function processDefinition(&$definition, $plugin_id) {
    $definition = NestedArray::mergeDeep($this->defaults, $definition);
    $definition['parent'] = (string) $definition['parent'];
    $definition['id'] = $plugin_id;
  }

  /**
   * Instanciates the discovery.
   */
  protected function getDiscovery() {
    if (empty($this->discovery)) {
      $yaml = new YamlDiscovery('menu_links', $this->moduleHandler->getModuleDirectories());
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($yaml);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    // Since this function is called rarely, instantiate the discovery here.
    $definitions = $this->getDiscovery()->getDefinitions();

    $this->moduleHandler->alter('menu_links', $definitions);

    foreach ($definitions as $plugin_id => &$definition) {
      $definition['id'] = $plugin_id;
      $this->processDefinition($definition, $plugin_id);
    }

    // If this plugin was provided by a module that does not exist, remove the
    // plugin definition.
    foreach ($definitions as $plugin_id => $plugin_definition) {
      if (!empty($plugin_definition['provider']) && !$this->moduleHandler->moduleExists($plugin_definition['provider'])) {
        unset($definitions[$plugin_id]);
      }
      else {
        // Any link found here is flagged as discovered, so it can be purged
        // if it does not exist in the future.
        $definitions[$plugin_id]['discovered'] = 1;
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuild() {
    // Fetch the list of existing menus, in case some are not longer populated
    // after the rebuild.
    $before_menus = $this->treeStorage->getMenuNames();
    $definitions = $this->getDefinitions();
    // Apply overrides from config.
    $overrides = $this->overrides->loadMultipleOverrides(array_keys($definitions));
    foreach ($overrides as $id => $changes) {
      if (!empty($definitions[$id])) {
        $definitions[$id] = $changes + $definitions[$id];
      }
    }
    $this->treeStorage->rebuild($definitions);
    $affected_menus = $this->treeStorage->getMenuNames() + $before_menus;
    Cache::invalidateTags(array('menu' => $affected_menus));
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    $definition = $this->treeStorage->load($plugin_id);
    if (empty($definition) && $exception_on_invalid) {
      throw new PluginNotFoundException("$plugin_id could not be found.");
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDefinition($plugin_id) {
    return (bool) $this->getDefinition($plugin_id, FALSE);
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface
   *   A menu link instance.
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    return $this->factory->createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    if (isset($options['id'])) {
      return $this->createInstance($options['id']);
    }
  }

  /**
   * Returns an array containing all links for a menu.
   *
   * @param string $menu_name
   *   The name of the menu whose links should be returned.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface[]
   *   An array of menu link plugin instances keyed by ID.
   */
  public function loadLinks($menu_name) {
    $instances = array();
    $loaded = $this->treeStorage->loadByProperties(array('menu_name' => $menu_name));
    foreach ($loaded as $plugin_id => $definition) {
      $instances[$plugin_id] = $this->createInstance($plugin_id);
    }
    return $instances;
  }

  /**
   * Deletes all links for a menu.
   *
   * @todo - this should really only be called as part of the flow of
   * deleting a menu entity, so maybe we should load it and make sure it's
   * not locked?
   *
   * @param string $menu_name
   *   The name of the menu whose links will be deleted.
   */
  public function deleteLinksInMenu($menu_name) {
    $affected_menus = array($menu_name => $menu_name);
    foreach ($this->treeStorage->loadByProperties(array('menu_name' => $menu_name)) as $plugin_id => $definition) {
      $instance = $this->createInstance($plugin_id);
      if ($instance->isResetable()) {
        $new_instance = $this->resetInstance($instance);
        $affected_menus[$new_instance->getMenuName()] = $new_instance->getMenuName();
      }
      elseif ($instance->isDeletable()) {
        $this->deleteInstance($instance, TRUE);
      }
    }
    Cache::invalidateTags(array('menu' => $affected_menus));
  }

  /**
   * Helper function to delete a specific instance.
   */
  protected function deleteInstance(MenuLinkInterface $instance, $persist) {
    $id = $instance->getPluginId();
    if ($instance->isDeletable()) {
      if ($persist) {
        $instance->deleteLink();
      }
    }
    else {
      throw new PluginException(sprintf("Menu link plugin with ID %s does not support deletion", $id));
    }
    $this->treeStorage->delete($id);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink($id, $persist = TRUE) {
    $definition = $this->treeStorage->load($id);
    // It's possible the definition has already been deleted, or doesn't exist.
    if ($definition) {
      $instance = $this->createInstance($id);
      $this->deleteInstance($instance, $persist);
      // Many children may have moved.
      $this->resetDefinitions();
      Cache::invalidateTags(array('menu' => array($definition['menu_name'])));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function menuNameInUse($menu_name) {
    $this->treeStorage->menuNameInUse($menu_name);
  }

  /**
   * {@inheritdoc}
   */
  public function countMenuLinks($menu_name = NULL) {
    return $this->treeStorage->countMenuLinks($menu_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getParentIds($id) {
    if ($this->getDefinition($id, FALSE)) {
      return $this->treeStorage->getRootPathIds($id);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildIds($id) {
    if ($this->getDefinition($id, FALSE)) {
      return $this->treeStorage->getAllChildIds($id);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadLinksByRoute($route_name, array $route_parameters = array(), $include_hidden = FALSE) {
    $instances = array();
    $loaded = $this->treeStorage->loadByRoute($route_name, $route_parameters, $include_hidden);
    foreach ($loaded as $plugin_id => $definition) {
      $instances[$plugin_id] = $this->createInstance($plugin_id);
    }
    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function createLink($id, array $definition) {
    // Add defaults and other stuff, so there is no requirement to specify
    // everything.
    $this->processDefinition($definition, $id);

    // Store the new link in the tree and invalidate some caches.
    $affected_menus = $this->treeStorage->save($definition);
    Cache::invalidateTags(array('menu' => $affected_menus));
    return $this->createInstance($id);
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink($id, array $new_definition_values, $persist = TRUE) {
    $instance = $this->createInstance($id);
    if ($instance) {
      $new_definition_values['id'] = $id;
      $changed_definition = $instance->updateLink($new_definition_values, $persist);
      $affected_menus = $this->treeStorage->save($changed_definition);
      $this->moduleHandler->invokeAll('menu_link_update', array($changed_definition));
      $this->resetDefinitions();
      Cache::invalidateTags(array('menu' => $affected_menus));
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function resetLink($id) {
    $instance = $this->createInstance($id);
    $affected_menus[$instance->getMenuName()] = $instance->getMenuName();
    $new_instance = $this->resetInstance($instance);
    $affected_menus[$new_instance->getMenuName()] = $new_instance->getMenuName();
    Cache::invalidateTags(array('menu' => $affected_menus));
    return $new_instance;
  }

  /**
   * Resets the menu link to its default settings.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $instance
   *   The menu link which should be reset.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface
   *   The reset menu link.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown when the menu link is not resetable.
   */
  protected function resetInstance(MenuLinkInterface $instance) {
    $id = $instance->getPluginId();

    if (!$instance->isResetable()) {
      throw new PluginException(String::format('Menu link %id is not resetable', array('%id' => $id)));
    }
    // Get the original data from disk, reset the override and re-save the menu
    // tree for this link.
    $definition = $this->getDefinitions()[$id];
    $this->overrides->deleteOverride($id);
    $this->treeStorage->save($definition);
    return $this->createInstance($id);
  }

  /**
   * {@inheritdoc}
   */
  public function resetDefinitions() {
    $this->treeStorage->resetDefinitions();
  }

}
