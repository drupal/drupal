<?php

namespace Drupal\help;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;

/**
 * Provides the default help_topic manager.
 *
 * Modules and themes can provide help topics in .html.twig files called
 * provider.name_of_topic.html.twig inside the module or theme sub-directory
 * help_topics. The provider is validated to be the extension that provides the
 * help topic.
 *
 * The Twig file must contain YAML front matter with a key named 'label'. It can
 * also contain keys named 'top_level' and 'related'. For example:
 * @code
 * ---
 * label: 'Configuring error responses, including 403/404 pages'
 *
 * # Related help topics in an array.
 * related:
 *   - core.config_basic
 *   - core.maintenance
 *
 * # If the value is true then the help topic will appear on admin/help.
 * top_level: true
 * ---
 * @endcode
 *
 * In addition, modules wishing to add plugins can define them in a
 * module_name.help_topics.yml file, with the plugin ID as the heading for
 * each entry, and these properties:
 * - id: The plugin ID.
 * - class: The name of your plugin class, implementing
 *   \Drupal\help\HelpTopicPluginInterface.
 * - top_level: TRUE if the topic is top-level.
 * - related: Array of IDs of topics this one is related to.
 * - Additional properties that your plugin class needs, such as 'label'.
 *
 * You can also provide an entry that designates a plugin deriver class in your
 * help_topics.yml file, with a heading giving a prefix ID for your group of
 * derived plugins, and a 'deriver' property giving the name of a class
 * implementing \Drupal\Component\Plugin\Derivative\DeriverInterface. Example:
 * @code
 * mymodule_prefix:
 *   deriver: 'Drupal\mymodule\Plugin\Deriver\HelpTopicDeriver'
 * @endcode
 *
 * @ingroup help_docs
 *
 * @see \Drupal\help\HelpTopicDiscovery
 * @see \Drupal\help\HelpTopicTwig
 * @see \Drupal\help\HelpTopicTwigLoader
 * @see \Drupal\help\HelpTopicPluginInterface
 * @see \Drupal\help\HelpTopicPluginBase
 * @see hook_help_topics_info_alter()
 * @see plugin_api
 * @see \Drupal\Component\Plugin\Derivative\DeriverInterface
 */
class HelpTopicPluginManager extends DefaultPluginManager implements HelpTopicPluginManagerInterface {

  /**
   * Provides default values for all help topic plugins.
   *
   * @var array
   */
  protected $defaults = [
    // The plugin ID.
    'id' => '',
    // The title of the help topic plugin.
    'label' => '',
    // Whether or not the topic should appear on the help topics list.
    'top_level' => '',
    // List of related topic machine names.
    'related' => [],
    // The class used to instantiate the plugin.
    'class' => '',
  ];

  /**
   * Constructs a new HelpTopicManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param string $root
   *   The app root.
   */
  public function __construct(ModuleHandlerInterface $module_handler, protected ThemeHandlerInterface $themeHandler, CacheBackendInterface $cache_backend, protected string $root) {
    // Note that the parent construct is not called because this class does not use
    // annotated class discovery.
    $this->moduleHandler = $module_handler;
    $this->alterInfo('help_topics_info');
    $this->setCacheBackend($cache_backend, 'help_topics');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $module_directories = $this->moduleHandler->getModuleDirectories();
      $all_directories = array_merge(
        ['core' => $this->root . '/core'],
        $module_directories,
        $this->themeHandler->getThemeDirectories()
      );

      // Search for Twig help topics in subdirectory help_topics, under
      // modules/profiles, themes, and the core directory.
      $all_directories = array_map(function ($dir) {
        return [$dir . '/help_topics'];
      }, $all_directories);
      $discovery = new HelpTopicDiscovery($all_directories);

      // Also allow modules/profiles to extend help topic discovery to their
      // own plugins and derivers, in mymodule.help_topics.yml files.
      $discovery = new YamlDiscoveryDecorator($discovery, 'help_topics', $module_directories);
      $discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
      $this->discovery = $discovery;
    }
    return $this->discovery;
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
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();

    // At this point the plugin list only contains valid plugins. Ensure all
    // related plugins exist and the relationship is bi-directional. This
    // ensures topics are listed on their related topics.
    foreach ($definitions as $plugin_id => $plugin_definition) {
      foreach ($plugin_definition['related'] as $key => $related_id) {
        // If the related help topic does not exist it might be for a module
        // that is not installed. Remove it.
        // @todo Discuss this more as this could cause silent errors but it
        //   offers useful functionality to relate to a help topic provided by
        //   extensions that are yet to be installed.
        //   https://www.drupal.org/i/3360133
        if (!isset($definitions[$related_id])) {
          unset($definitions[$plugin_id]['related'][$key]);
          continue;
        }
        // Make the related relationship bi-directional.
        if (isset($definitions[$related_id]) && !in_array($plugin_id, $definitions[$related_id]['related'], TRUE)) {
          $definitions[$related_id]['related'][] = $plugin_id;
        }
      }
    }
    return $definitions;
  }

}
