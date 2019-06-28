<?php

namespace Drupal\help_topics;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the default help_topic manager.
 *
 * Modules and themes can provide help topics in .html.twig files called
 * provider.name_of_topic.html.twig inside the module or theme sub-directory
 * help_topics. The provider is validated to be the extension that provides the
 * help topic.
 *
 * The Twig file must contain a meta tag named 'help_topic:label'. It can also
 * contain meta tags named 'help_topic:top_level' and 'help_topic:related'. For
 * example:
 * @code
 * <!–– The label/title of the topic. -->
 * <meta name="help_topic:label" content="Configuring error responses, including 403/404 pages"/>
 *
 * <!–– Related help topics in a comma separated help topic ID list. -->
 * <meta name="help_topic:related" content="core.config_basic,core.maintenance"/>
 *
 * <!–– If present then the help topic will appear on admin/help. -->
 * <meta name="help_topic:top_level"/>
 * @endcode
 *
 * @see \Drupal\help_topics\HelpTopicDiscovery
 * @see \Drupal\help_topics\HelpTopicTwig
 * @see \Drupal\help_topics\HelpTopicTwigLoader
 * @see \Drupal\help_topics\HelpTopicPluginInterface
 * @see \Drupal\help_topics\HelpTopicPluginBase
 * @see hook_help_topics_info_alter()
 *
 * @internal
 *   Help Topic is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
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
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new HelpTopicManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, CacheBackendInterface $cache_backend) {
    // Note that the parent construct is not called because this not use
    // annotated class discovery.
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->alterInfo('help_topics_info');
    // Use the 'config:core.extension' cache tag so the plugin cache is
    // invalidated on theme install and uninstall.
    $this->setCacheBackend($cache_backend, 'help_topics', ['config:core.extension']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      // We want to find help topic plugins in core, modules and themes in
      // a sub-directory called help_topics.
      $directories = array_merge(
        ['core'],
        $this->moduleHandler->getModuleDirectories(),
        $this->themeHandler->getThemeDirectories()
      );

      $directories = array_map(function ($dir) {
        return [$dir . '/help_topics'];
      }, $directories);

      $this->discovery = new HelpTopicDiscovery($directories);
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
        //   offers useful functionality to relate to help topic provided by
        //   extensions that are yet to be installed.
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
