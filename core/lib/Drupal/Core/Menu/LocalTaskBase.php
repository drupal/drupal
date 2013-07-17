<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\LocalTaskBase.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\Translator\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides defaults and base methods for menu local tasks plugins.
 */
abstract class LocalTaskBase extends PluginBase implements LocalTaskInterface, ContainerFactoryPluginInterface{

  /**
   * String translation object.
   *
   * @var \Drupal\Core\StringTranslation\Translator\TranslatorInterface
   */
  protected $t;

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
   * Constructs a \Drupal\system\Plugin\LocalTaskBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\Translator\TranslatorInterface $string_translation
   *   The string translation object.
   * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $generator
   *   The url generator object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, TranslatorInterface $string_translation, UrlGeneratorInterface $generator) {
    // This is available for subclasses that need to translate a dynamic title.
    $this->t = $string_translation;
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
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    // Subclasses may set a request into the generator or use any desired method
    // to generate the path.
    // @todo - use the new method from https://drupal.org/node/2031353
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
