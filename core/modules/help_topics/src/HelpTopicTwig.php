<?php

namespace Drupal\help_topics;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\TwigEnvironment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents a help topic plugin whose definition comes from a Twig file.
 *
 * @see \Drupal\help_topics\HelpTopicDiscovery
 * @see \Drupal\help_topics\HelpTopicTwigLoader
 * @see \Drupal\help_topics\HelpTopicPluginManager
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class HelpTopicTwig extends HelpTopicPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Twig environment.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TwigEnvironment $twig) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->twig = $twig;
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
