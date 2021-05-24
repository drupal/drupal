<?php

namespace Drupal\help_topics;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Defines and registers Drupal Twig extensions for rendering help topics.
 */
class HelpTwigExtension extends AbstractExtension {

  use StringTranslationTrait;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The help topic plugin manager.
   *
   * @var \Drupal\help_topics\HelpTopicPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a \Drupal\help_topics\HelpTwigExtension.
   *
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\help_topics\HelpTopicPluginManagerInterface $plugin_manager
   *   The help topic plugin manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(AccessManagerInterface $access_manager, HelpTopicPluginManagerInterface $plugin_manager, TranslationInterface $string_translation) {
    $this->accessManager = $access_manager;
    $this->pluginManager = $plugin_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('help_route_link', [$this, 'getRouteLink']),
      new TwigFunction('help_topic_link', [$this, 'getTopicLink']),
    ];
  }

  /**
   * Returns a link or plain text, given text, route name, and parameters.
   *
   * @param string $text
   *   The link text.
   * @param string $route
   *   The name of the route.
   * @param array $parameters
   *   (optional) An associative array of route parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional options. The 'absolute'
   *   option is forced to be TRUE.
   *
   * @return array
   *   A render array with a generated absolute link to the given route. If
   *   the user does not have permission for the route, or an exception occurs,
   *   such as a missing route or missing parameters, the render array is for
   *   the link text as a plain string instead.
   *
   * @see \Drupal\Core\Template\TwigExtension::getUrl()
   */
  public function getRouteLink(string $text, string $route, array $parameters = [], array $options = []): array {
    assert($this->accessManager instanceof AccessManagerInterface, "The access manager hasn't been set up. Any configuration YAML file with a service directive dealing with the Twig configuration can cause this, most likely found in a recently installed or changed module.");

    $bubbles = new BubbleableMetadata();
    $bubbles->addCacheTags(['route_match']);

    try {
      $access_object = $this->accessManager->checkNamedRoute($route, $parameters, NULL, TRUE);
      $bubbles->addCacheableDependency($access_object);

      if ($access_object->isAllowed()) {
        $options['absolute'] = TRUE;
        $url = Url::fromRoute($route, $parameters, $options);
        // Generate the URL to check for parameter problems and collect
        // cache metadata.
        $generated = $url->toString(TRUE);
        $bubbles->addCacheableDependency($generated);
        $build = [
          '#title' => $text,
          '#type' => 'link',
          '#url' => $url,
        ];
      }
      else {
        // If the user doesn't have access, return the link text.
        $build = ['#markup' => $text];
      }
    }
    catch (RouteNotFoundException | MissingMandatoryParametersException | InvalidParameterException $e) {
      // If the route had one of these exceptions, return the link text.
      $build = ['#markup' => $text];
    }
    $bubbles->applyTo($build);
    return $build;
  }

  /**
   * Returns a link to a help topic, or the title of the topic.
   *
   * @param string $topic_id
   *   The help topic ID.
   *
   * @return array
   *   A render array with a generated absolute link to the given topic. If
   *   the user does not have permission to view the topic, or an exception
   *   occurs, such as the topic not being defined due to a module not being
   *   installed, a default string is returned.
   *
   * @see \Drupal\Core\Template\TwigExtension::getUrl()
   */
  public function getTopicLink(string $topic_id): array {
    assert($this->pluginManager instanceof HelpTopicPluginManagerInterface, "The plugin manager hasn't been set up. Any configuration YAML file with a service directive dealing with the Twig configuration can cause this, most likely found in a recently installed or changed module.");

    $bubbles = new BubbleableMetadata();
    $bubbles->addCacheableDependency($this->pluginManager);
    try {
      $plugin = $this->pluginManager->createInstance($topic_id);
    }
    catch (PluginNotFoundException $e) {
      // Not a topic.
      $plugin = FALSE;
    }

    if ($plugin) {
      $parameters = ['id' => $topic_id];
      $route = 'help.help_topic';
      $build = $this->getRouteLink($plugin->getLabel(), $route, $parameters);
      $bubbles->addCacheableDependency($plugin);
    }
    else {
      $build = [
        '#markup' => $this->t('Missing help topic %topic', [
          '%topic' => $topic_id,
        ]),
      ];
    }

    $bubbles->applyTo($build);
    return $build;
  }

}
