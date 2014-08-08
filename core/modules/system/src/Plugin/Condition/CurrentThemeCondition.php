<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Condition\CurrentThemeCondition.
 */

namespace Drupal\system\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Current Theme' condition.
 *
 * @Condition(
 *   id = "current_theme",
 *   label = @Translation("Current Theme"),
 * )
 */
class CurrentThemeCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The theme negotiator.
   *
   * @var \Drupal\Core\Theme\ThemeNegotiatorInterface
   */
  protected $themeNegotiator;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a CurrentThemeCondition condition plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Theme\ThemeNegotiatorInterface $theme_negotiator
   *   The theme negotiator.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ThemeNegotiatorInterface $theme_negotiator, ThemeHandlerInterface $theme_handler, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->themeNegotiator = $theme_negotiator;
    $this->themeHandler = $theme_handler;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('theme.negotiator'),
      $container->get('theme_handler'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('theme' => '') + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['theme'] = array(
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#default_value' => $this->configuration['theme'],
      '#options' => array_map(function ($theme_info) {
        return $theme_info->info['name'];
      }, $this->themeHandler->listInfo()),
    );
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['theme'] = $form_state->getValue('theme');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (!$this->configuration['theme']) {
      return TRUE;
    }

    return $this->themeNegotiator->determineActiveTheme($this->routeMatch) == $this->configuration['theme'];
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if ($this->isNegated()) {
      return $this->t('The current theme is not @theme', array('@theme' => $this->configuration['theme']));
    }

    return $this->t('The current theme is @theme', array('@theme' => $this->configuration['theme']));
  }

}
