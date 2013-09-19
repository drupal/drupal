<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\LocalActionBase.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Menu\LocalActionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\Translator\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides defaults and base methods for menu local action plugins.
 *
 * @todo This class needs more documentation and/or @see references.
 */
abstract class LocalActionBase extends PluginBase implements LocalActionInterface, ContainerFactoryPluginInterface {

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
   * Constructs a LocalActionBase object.
   *
   * @param \Drupal\Core\StringTranslation\Translator\TranslatorInterface $string_translation
   *   A translator object for use by subclasses generating localized titles.
   * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $generator
   *   A URL generator object used to get the path from the route.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(TranslatorInterface $string_translation,  UrlGeneratorInterface $generator, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->generator = $generator;
    // This is available for subclasses that need to translate a dynamic title.
    $this->t = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $container->get('string_translation'),
      $container->get('url_generator'),
      $configuration,
      $plugin_id,
      $plugin_definition
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

}
