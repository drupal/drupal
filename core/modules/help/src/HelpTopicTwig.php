<?php

namespace Drupal\help;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\TwigEnvironment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents a help topic plugin whose definition comes from a Twig file.
 *
 * @see \Drupal\help\HelpTopicDiscovery
 * @see \Drupal\help\HelpTopicTwigLoader
 * @see \Drupal\help\HelpTopicPluginManager
 *
 * @internal
 *   Plugin classes are internal.
 */
class HelpTopicTwig extends HelpTopicPluginBase implements ContainerFactoryPluginInterface {

  /**
   * HelpTopicPluginBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   *   The Twig environment.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected TwigEnvironment $twig) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('twig')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return [
      '#markup' => $this->twig->load('@help_topics/' . $this->getPluginId() . '.html.twig')->render(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['core.extension'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
