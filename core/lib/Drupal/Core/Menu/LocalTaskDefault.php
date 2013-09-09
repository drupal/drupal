<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\LocalTaskDefault.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Default object used for LocalTaskPlugins.
 */
class LocalTaskDefault extends PluginBase implements LocalTaskInterface, ContainerFactoryPluginInterface {

  /**
   * String translation object.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * URL generator object.
   *
   * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
   */
  protected $generator;

  /**
   * TRUE if this plugin is forced active for options attributes.
   *
   * @var bool
   */
  protected $active = FALSE;

  /**
   * Constructs a \Drupal\system\Plugin\LocalTaskDefault object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation object.
   * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $generator
   *   The url generator object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, TranslationInterface $string_translation, UrlGeneratorInterface $generator) {
    $this->stringTranslation = $string_translation;
    $this->generator = $generator;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('url_generator')
    );
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->stringTranslation->translate($string, $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return $this->pluginDefinition['route_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    // Subclasses may pull in the request or specific attributes as parameters.
    return $this->t($this->pluginDefinition['title']);
  }

  /**
   * {@inheritdoc}
   *
   * @todo update based on https://drupal.org/node/2045267
   */
  public function getPath() {
    // Subclasses may set a request into the generator or use any desired method
    // to generate the path.
    $path = $this->generator->generate($this->getRouteName());
    // In order to get the Drupal path the base URL has to be stripped off.
    $base_url = $this->generator->getContext()->getBaseUrl();
    if (!empty($base_url) && strpos($path, $base_url) === 0) {
      $path = substr($path, strlen($base_url));
    }
    return trim($path, '/');
  }

  /**
   * Returns the weight of the local task.
   *
   * @return int
   *   The weight of the task. If not defined in the annotation returns 0 by
   *   default or -10 for the root tab.
   */
  public function getWeight() {
    // By default the weight is 0, or -10 for the root tab.
    if (!isset($this->pluginDefinition['weight'])) {
      if ($this->pluginDefinition['tab_root_id'] == $this->pluginDefinition['id']) {
        $this->pluginDefinition['weight'] = -10;
      }
      else {
        $this->pluginDefinition['weight'] = 0;
      }
    }
    return (int) $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $options = $this->pluginDefinition['options'];
    if ($this->active) {
      if (empty($options['attributes']['class']) || !in_array('active', $options['attributes']['class'])) {
        $options['attributes']['class'][] = 'active';
      }
    }
    return (array) $options;
  }

  /**
   * {@inheritdoc}
   */
  public function setActive($active = TRUE) {
    $this->active = $active;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getActive() {
    return $this->active;
  }

}
