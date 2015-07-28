<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLinkManager.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\NestedArray;
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
   * Provides some default values for the definition of all menu link plugins.
   *
   * @todo Decide how to keep these field definitions in sync.
   *   https://www.drupal.org/node/2302085
   *
   * @var array
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
    // The static title for the menu link. You can specify placeholders like on
    // any translatable string and the values in title_arguments.
    'title' => '',
    // The values for the menu link placeholders.
    'title_arguments' => array(),
    // A context for the title string.
    // @see \Drupal\Core\StringTranslation\TranslationInterface::translate()
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
    'enabled' => 1,
    // The name of the module providing this link.
    'provider' => '',
    'metadata' => array(),
    // Default class for local task implementations.
    'class' => 'Drupal\Core\Menu\MenuLinkDefault',
    'form_class' => 'Drupal\Core\Menu\Form\MenuLinkDefaultForm',
    // The plugin ID. Set by the plugin system based on the top-level YAML key.
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
   * Service providing overrides for static links.
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
   * Constructs a \Drupal\Core\Menu\MenuLinkManager object.
   *
   * @param \Drupal\Core\Menu\MenuTreeStorageInterface $tree_storage
   *   The menu link tree storage.
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $overrides
   *   The service providing overrides for static links.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(MenuTreeStorageInterface $tree_storage, StaticMenuLinkOverridesInterface $overrides, ModuleHandlerInterface $module_handler) {
    $this->treeStorage = $tree_storage;
    $this->overrides = $overrides;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Performs extra processing on plugin definitions.
   *
   * By default we add defaults for the type to the definition. If a type has
   * additional processing logic, the logic can be added by replacing or
   * extending this method.
   *
   * @param array $definition
   *   The definition to be processed and modified by reference.
   * @param $plugin_id
   *   The ID of the plugin this definition is being used for.
   */
  protected function processDefinition(array &$definition, $plugin_id) {
    $definition = NestedArray::mergeDeep($this->defaults, $definition);
    // Typecast so NULL, no parent, will be an empty string since the parent ID
    // should be a string.
    $definition['parent'] = (string) $definition['parent'];
    $definition['id'] = $plugin_id;
  }

  /**
   * Gets the plugin discovery.
   *
   * @return \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('links.menu', $this->moduleHandler->getModuleDirectories());
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * Gets the plugin factory.
   *
   * @return \Drupal\Component\Plugin\Factory\FactoryInterface
   */
  protected function getFactory() {
    if (!isset($this->factory)) {
      $this->factory = new ContainerFactory($this);
    }
    return $this->factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    // Since this function is called rarely, instantiate the discovery here.
    $definitions = $this->getDiscovery()->getDefinitions();

    $this->moduleHandler->alter('menu_links_discovered', $definitions);

    foreach ($definitions as $plugin_id => &$definition) {
      $definition['id'] = $plugin_id;
      $this->processDefinition($definition, $plugin_id);
    }

    // If this plugin was provided by a module that does not exist, remove the
    // plugin definition.
    // @todo Address what to do with an invalid plugin.
    //   https://www.drupal.org/node/2302623
    foreach ($definitions as $plugin_id => $plugin_definition) {
      if (!empty($plugin_definition['provider']) && !$this->moduleHandler->moduleExists($plugin_definition['provider'])) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuild() {
    $definitions = $this->getDefinitions();
    // Apply overrides from config.
    $overrides = $this->overrides->loadMultipleOverrides(array_keys($definitions));
    foreach ($overrides as $id => $changes) {
      if (!empty($definitions[$id])) {
        $definitions[$id] = $changes + $definitions[$id];
      }
    }
    $this->treeStorage->rebuild($definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    $definition = $this->treeStorage->load($plugin_id);
    if (empty($definition) && $exception_on_invalid) {
      throw new PluginNotFoundException($plugin_id);
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
   * Returns a pre-configured menu link plugin instance.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface
   *   A menu link instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    return $this->getFactory()->createInstance($plugin_id, $configuration);
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
   * {@inheritdoc}
   */
  public function deleteLinksInMenu($menu_name) {
    foreach ($this->treeStorage->loadByProperties(array('menu_name' => $menu_name)) as $plugin_id => $definition) {
      $instance = $this->createInstance($plugin_id);
      if ($instance->isDeletable()) {
        $this->deleteInstance($instance, TRUE);
      }
      elseif ($instance->isResettable()) {
        $new_instance = $this->resetInstance($instance);
        $affected_menus[$new_instance->getMenuName()] = $new_instance->getMenuName();
      }
    }
  }

  /**
   * Deletes a specific instance.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $instance
   *   The plugin instance to be deleted.
   * @param bool $persist
   *   If TRUE, calls MenuLinkInterface::deleteLink() on the instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the plugin instance does not support deletion.
   */
  protected function deleteInstance(MenuLinkInterface $instance, $persist) {
    $id = $instance->getPluginId();
    if ($instance->isDeletable()) {
      if ($persist) {
        $instance->deleteLink();
      }
    }
    else {
      throw new PluginException("Menu link plugin with ID '$id' does not support deletion");
    }
    $this->treeStorage->delete($id);
  }

  /**
   * {@inheritdoc}
   */
  public function removeDefinition($id, $persist = TRUE) {
    $definition = $this->treeStorage->load($id);
    // It's possible the definition has already been deleted, or doesn't exist.
    if ($definition) {
      $instance = $this->createInstance($id);
      $this->deleteInstance($instance, $persist);
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
  public function loadLinksByRoute($route_name, array $route_parameters = array(), $menu_name = NULL) {
    $instances = array();
    $loaded = $this->treeStorage->loadByRoute($route_name, $route_parameters, $menu_name);
    foreach ($loaded as $plugin_id => $definition) {
      $instances[$plugin_id] = $this->createInstance($plugin_id);
    }
    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function addDefinition($id, array $definition) {
    if ($this->treeStorage->load($id) || $id === '') {
      throw new PluginException("The ID $id already exists as a plugin definition or is not valid");
    }
    // Add defaults, so there is no requirement to specify everything.
    $this->processDefinition($definition, $id);
    // Store the new link in the tree.
    $this->treeStorage->save($definition);
    return $this->createInstance($id);
  }

  /**
   * {@inheritdoc}
   */
  public function updateDefinition($id, array $new_definition_values, $persist = TRUE) {
    $instance = $this->createInstance($id);
    if ($instance) {
      $new_definition_values['id'] = $id;
      $changed_definition = $instance->updateLink($new_definition_values, $persist);
      $this->treeStorage->save($changed_definition);
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function resetLink($id) {
    $instance = $this->createInstance($id);
    $new_instance = $this->resetInstance($instance);
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
   *   Thrown when the menu link is not resettable.
   */
  protected function resetInstance(MenuLinkInterface $instance) {
    $id = $instance->getPluginId();

    if (!$instance->isResettable()) {
      throw new PluginException("Menu link $id is not resettable");
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
