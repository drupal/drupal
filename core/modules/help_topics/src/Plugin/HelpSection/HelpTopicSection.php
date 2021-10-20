<?php

namespace Drupal\help_topics\Plugin\HelpSection;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\help_topics\SearchableHelpInterface;
use Drupal\help_topics\HelpTopicPluginInterface;
use Drupal\help_topics\HelpTopicPluginManagerInterface;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\help\Plugin\HelpSection\HelpSectionPluginBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\StringTranslation\TranslationManager;
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
class HelpTopicSection extends HelpSectionPluginBase implements ContainerFactoryPluginInterface, SearchableHelpInterface {

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
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $cacheableMetadata;

  /**
   * The Renderer service to format the username and node.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The default language object.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $defaultLanguage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $stringTranslation;

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
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Language\LanguageDefault $default_language
   *   The default language object.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation_manager
   *   The translation manager. We are using a method that doesn't exist on an
   *   interface, so require this class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, HelpTopicPluginManagerInterface $plugin_manager, RendererInterface $renderer, LanguageDefault $default_language, LanguageManagerInterface $language_manager, TranslationManager $translation_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManager = $plugin_manager;
    $this->renderer = $renderer;
    $this->defaultLanguage = $default_language;
    $this->languageManager = $language_manager;
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.help_topic'),
      $container->get('renderer'),
      $container->get('language.default'),
      $container->get('language_manager'),
      $container->get('string_translation')
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
   *   The top level help topic plugins.
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
          return $a->getPluginId() <=> $b->getPluginId();
        }
        return strnatcasecmp($a_label, $b_label);
      });
    }
    return $this->topLevelPlugins;
  }

  /**
   * {@inheritdoc}
   */
  public function listSearchableTopics() {
    $definitions = $this->pluginManager->getDefinitions();
    return array_column($definitions, 'id');
  }

  /**
   * {@inheritdoc}
   */
  public function renderTopicForSearch($topic_id, LanguageInterface $language) {
    $plugin = $this->pluginManager->createInstance($topic_id);
    if (!$plugin) {
      return [];
    }

    // We are rendering this topic for search indexing or search results,
    // possibly in a different language than the current language. The topic
    // title and body come from translatable things in the Twig template, so we
    // need to set the default language to the desired language, render them,
    // then restore the default language so we do not affect other cron
    // processes. Also, just in case there is an exception, wrap the whole
    // thing in a try/finally block, and reset the language in the finally part.
    $old_language = $this->defaultLanguage->get();
    try {
      if ($old_language->getId() !== $language->getId()) {
        $this->defaultLanguage->set($language);
        $this->translationManager->setDefaultLangcode($language->getId());
        $this->languageManager->reset();
      }
      $topic = [];

      // Render the title in this language.
      $title_build = [
        'title' => [
          '#type' => '#markup',
          '#markup' => $plugin->getLabel(),
        ],
      ];
      $topic['title'] = $this->renderer->renderPlain($title_build);
      $cacheable_metadata = CacheableMetadata::createFromRenderArray($title_build);

      // Render the body in this language. For this, we need to set up a render
      // context, because the Twig plugins that provide the body assumes one
      // is present.
      $context = new RenderContext();
      $build = [
        'body' => $this->renderer->executeInRenderContext($context, [$plugin, 'getBody']),
      ];
      $topic['text'] = $this->renderer->renderPlain($build);
      $cacheable_metadata->addCacheableDependency(CacheableMetadata::createFromRenderArray($build));
      $cacheable_metadata->addCacheableDependency($plugin);
      if (!$context->isEmpty()) {
        $cacheable_metadata->addCacheableDependency($context->pop());
      }

      // Add the other information.
      $topic['url'] = $plugin->toUrl();
      $topic['cacheable_metadata'] = $cacheable_metadata;
    }
    finally {
      // Restore the original language.
      if ($old_language->getId() !== $language->getId()) {
        $this->defaultLanguage->set($old_language);
        $this->translationManager->setDefaultLangcode($old_language->getId());
        $this->languageManager->reset();
      }
    }

    return $topic;
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
