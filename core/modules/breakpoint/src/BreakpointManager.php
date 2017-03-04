<?php

namespace Drupal\breakpoint;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Defines a breakpoint plugin manager to deal with breakpoints.
 *
 * Extension can define breakpoints in a EXTENSION_NAME.breakpoints.yml file
 * contained in the extension's base directory. Each breakpoint has the
 * following structure:
 * @code
 *   MACHINE_NAME:
 *     label: STRING
 *     mediaQuery: STRING
 *     weight: INTEGER
 *     multipliers:
 *       - STRING
 * @endcode
 * For example:
 * @code
 * bartik.mobile:
 *   label: mobile
 *   mediaQuery: '(min-width: 0px)'
 *   weight: 0
 *   multipliers:
 *     - 1x
 *     - 2x
 * @endcode
 * Optionally a breakpoint can provide a group key. By default an extensions
 * breakpoints will be placed in a group labelled with the extension name.
 *
 * @see \Drupal\breakpoint\Breakpoint
 * @see \Drupal\breakpoint\BreakpointInterface
 * @see plugin_api
 */
class BreakpointManager extends DefaultPluginManager implements BreakpointManagerInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    // Human readable label for breakpoint.
    'label' => '',
    // The media query for the breakpoint.
    'mediaQuery' => '',
    // Weight used for ordering breakpoints.
    'weight' => 0,
    // Breakpoint multipliers.
    'multipliers' => [],
    // The breakpoint group.
    'group' => '',
    // Default class for breakpoint implementations.
    'class' => 'Drupal\breakpoint\Breakpoint',
    // The plugin id. Set by the plugin system based on the top-level YAML key.
    'id' => '',
  ];

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Static cache of breakpoints keyed by group.
   *
   * @var array
   */
  protected $breakpointsByGroup;

  /**
   * The plugin instances.
   *
   * @var array
   */
  protected $instances = [];

  /**
   * Constructs a new BreakpointManager instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, CacheBackendInterface $cache_backend, TranslationInterface $string_translation) {
    $this->factory = new ContainerFactory($this);
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->setStringTranslation($string_translation);
    $this->alterInfo('breakpoints');
    $this->setCacheBackend($cache_backend, 'breakpoints', ['breakpoints']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('breakpoints', $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories());
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    // Allow custom groups and therefore more than one group per extension.
    if (empty($definition['group'])) {
      $definition['group'] = $definition['provider'];
    }
    // Ensure a 1x multiplier exists.
    if (!in_array('1x', $definition['multipliers'])) {
      $definition['multipliers'][] = '1x';
    }
    // Ensure that multipliers are sorted correctly.
    sort($definition['multipliers']);
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * {@inheritdoc}
   */
  public function getBreakpointsByGroup($group) {
    if (!isset($this->breakpointsByGroup[$group])) {
      if ($cache = $this->cacheBackend->get($this->cacheKey . ':' . $group)) {
        $this->breakpointsByGroup[$group] = $cache->data;
      }
      else {
        $breakpoints = [];
        foreach ($this->getDefinitions() as $plugin_id => $plugin_definition) {
          if ($plugin_definition['group'] == $group) {
            $breakpoints[$plugin_id] = $plugin_definition;
          }
        }
        uasort($breakpoints, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
        $this->cacheBackend->set($this->cacheKey . ':' . $group, $breakpoints, Cache::PERMANENT, ['breakpoints']);
        $this->breakpointsByGroup[$group] = $breakpoints;
      }
    }

    $instances = [];
    foreach ($this->breakpointsByGroup[$group] as $plugin_id => $definition) {
      if (!isset($this->instances[$plugin_id])) {
        $this->instances[$plugin_id] = $this->createInstance($plugin_id);
      }
      $instances[$plugin_id] = $this->instances[$plugin_id];
    }
    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroups() {
    // Use a double colon so as to not clash with the cache for each group.
    if ($cache = $this->cacheBackend->get($this->cacheKey . '::groups')) {
      $groups = $cache->data;
    }
    else {
      $groups = [];
      foreach ($this->getDefinitions() as $plugin_definition) {
        if (!isset($groups[$plugin_definition['group']])) {
          $groups[$plugin_definition['group']] = $plugin_definition['group'];
        }
      }
      $this->cacheBackend->set($this->cacheKey . '::groups', $groups, Cache::PERMANENT, ['breakpoints']);
    }
    // Get the labels. This is not cacheable due to translation.
    $group_labels = [];
    foreach ($groups as $group) {
      $group_labels[$group] = $this->getGroupLabel($group);
    }
    asort($group_labels);
    return $group_labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupProviders($group) {
    $providers = [];
    $breakpoints = $this->getBreakpointsByGroup($group);
    foreach ($breakpoints as $breakpoint) {
      $provider = $breakpoint->getProvider();
      $extension = FALSE;
      if ($this->moduleHandler->moduleExists($provider)) {
        $extension = $this->moduleHandler->getModule($provider);
      }
      elseif ($this->themeHandler->themeExists($provider)) {
        $extension = $this->themeHandler->getTheme($provider);
      }
      if ($extension) {
        $providers[$extension->getName()] = $extension->getType();
      }
    }
    return $providers;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();
    $this->breakpointsByGroup = NULL;
    $this->instances = [];
  }

  /**
   * Gets the label for a breakpoint group.
   *
   * @param string $group
   *   The breakpoint group.
   *
   * @return string
   *   The label.
   */
  protected function getGroupLabel($group) {
    // Extension names are not translatable.
    if ($this->moduleHandler->moduleExists($group)) {
      $label = $this->moduleHandler->getName($group);
    }
    elseif ($this->themeHandler->themeExists($group)) {
      $label = $this->themeHandler->getName($group);
    }
    else {
      // Custom group label that should be translatable.
      $label = $this->t($group, [], ['context' => 'breakpoint']);
    }
    return $label;
  }

}
