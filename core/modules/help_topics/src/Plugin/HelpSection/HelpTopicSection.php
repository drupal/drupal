<?php

namespace Drupal\help_topics\Plugin\HelpSection;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\help_topics\HelpTopicPluginInterface;
use Drupal\help_topics\HelpTopicPluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\help\Plugin\HelpSection\HelpSectionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the help topics list section for the help page.
 *
 * @HelpSection(
 *   id = "help_topics",
 *   title = @Translation("Topics"),
 *   weight = -10,
 *   description = @Translation("Topics can be provided by modules or themes. Top-level help topics on your site:"),
 *   permission = "access administration pages"
 * )
 *
 * @internal
 *   Help Topic is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class HelpTopicSection extends HelpSectionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The plugin manager.
   *
   * @var \Drupal\help_topics\HelpTopicPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The top level help topic plugins.
   *
   * @var \Drupal\help_topics\HelpTopicPluginInterface[]
   */
  protected $topLevelPlugins;

  /**
   * The merged top level help topic plugins cache metadata.
   */
  protected $cacheableMetadata;

  /**
   * Constructs a HelpTopicSection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\help_topics\HelpTopicPluginManagerInterface $plugin_manager
   *   The help topic plugin manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, HelpTopicPluginManagerInterface $plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.help_topic')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->getCacheMetadata()->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->getCacheMetadata()->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->getCacheMetadata()->getCacheMaxAge();
  }

  /**
   * {@inheritdoc}
   */
  public function listTopics() {
    // Map the top level help topic plugins to a list of topic links.
    return array_map(function (HelpTopicPluginInterface $topic) {
      return $topic->toLink();
    }, $this->getPlugins());
  }

  /**
   * Gets the top level help topic plugins.
   *
   * @return \Drupal\help_topics\HelpTopicPluginInterface[]
   *   The top level help topic plugins
   */
  protected function getPlugins() {
    if (!isset($this->topLevelPlugins)) {
      $definitions = $this->pluginManager->getDefinitions();

      // Get all the top level topics and merge their list cache tags.
      foreach ($definitions as $definition) {
        if ($definition['top_level']) {
          $this->topLevelPlugins[$definition['id']] = $this->pluginManager->createInstance($definition['id']);
        }
      }

      // Sort the top level topics by label and, if the labels match, then by
      // plugin ID.
      usort($this->topLevelPlugins, function (HelpTopicPluginInterface $a, HelpTopicPluginInterface $b) {
        $a_label = (string) $a->getLabel();
        $b_label = (string) $b->getLabel();
        if ($a_label === $b_label) {
          return $a->getPluginId() < $b->getPluginId() ? -1 : 1;
        }
        return strnatcasecmp($a_label, $b_label);
      });
    }
    return $this->topLevelPlugins;
  }

  /**
   * Gets the merged CacheableMetadata for all the top level help topic plugins.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   The merged CacheableMetadata for all the top level help topic plugins.
   */
  protected function getCacheMetadata() {
    if (!isset($this->cacheableMetadata)) {
      $this->cacheableMetadata = new CacheableMetadata();
      foreach ($this->getPlugins() as $plugin) {
        $this->cacheableMetadata->addCacheableDependency($plugin);
      }
    }
    return $this->cacheableMetadata;
  }

}
